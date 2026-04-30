import { execSync } from 'node:child_process';
import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';

const root = resolve(process.cwd());
const packagePath = resolve(root, 'package.json');
const composerPath = resolve(root, 'composer.json');
const statePath = resolve(root, '.build-version.json');

/**
 * @param {string} raw
 * @returns {{ major: number; minor: number; patch: number }}
 */
function parseSemver(raw) {
    const match = String(raw).trim().match(/^(\d+)\.(\d+)\.(\d+)$/);
    if (!match) {
        throw new Error(`Version inválida: ${raw}`);
    }

    return {
        major: Number(match[1]),
        minor: Number(match[2]),
        patch: Number(match[3]),
    };
}

/**
 * @param {{ major: number; minor: number; patch: number }} version
 * @param {'patch' | 'minor'} bumpType
 * @returns {{ major: number; minor: number; patch: number }}
 */
function bump(version, bumpType) {
    if (bumpType === 'minor') {
        return {
            major: version.major,
            minor: version.minor + 1,
            patch: 0,
        };
    }

    return {
        major: version.major,
        minor: version.minor,
        patch: version.patch + 1,
    };
}

/**
 * @param {{ major: number; minor: number; patch: number }} version
 * @returns {string}
 */
function toSemver(version) {
    return `${version.major}.${version.minor}.${version.patch}`;
}

/**
 * @param {string[]} subjects
 * @returns {'patch' | 'minor'}
 */
function detectBumpType(subjects) {
    const hasFeat = subjects.some((subject) => /^feat(\([^)]+\))?:/i.test(subject.trim()));
    return hasFeat ? 'minor' : 'patch';
}

/**
 * @returns {string[]}
 */
function commitSubjectsSinceLastBuild() {
    let state = null;
    if (existsSync(statePath)) {
        state = JSON.parse(readFileSync(statePath, 'utf8'));
    }

    const head = execSync('git rev-parse HEAD', { encoding: 'utf8' }).trim();
    const lastCommit = state?.last_commit && String(state.last_commit).trim() !== '' ? String(state.last_commit).trim() : null;

    if (lastCommit === head) {
        return [];
    }

    if (lastCommit) {
        const out = execSync(`git log --format=%s ${lastCommit}..${head}`, { encoding: 'utf8' }).trim();
        return out === '' ? [] : out.split('\n');
    }

    const subject = execSync('git log -1 --format=%s', { encoding: 'utf8' }).trim();
    return subject === '' ? [] : [subject];
}

const packageJson = JSON.parse(readFileSync(packagePath, 'utf8'));
const composerJson = JSON.parse(readFileSync(composerPath, 'utf8'));
const currentVersion = parseSemver(packageJson.version ?? '0.0.0');
const subjects = commitSubjectsSinceLastBuild();
const bumpType = detectBumpType(subjects);
const nextVersion = toSemver(bump(currentVersion, bumpType));
const head = execSync('git rev-parse HEAD', { encoding: 'utf8' }).trim();

packageJson.version = nextVersion;
composerJson.version = nextVersion;

writeFileSync(packagePath, `${JSON.stringify(packageJson, null, 4)}\n`, 'utf8');
writeFileSync(composerPath, `${JSON.stringify(composerJson, null, 4)}\n`, 'utf8');
writeFileSync(
    statePath,
    `${JSON.stringify(
        {
            version: nextVersion,
            bump_type: bumpType,
            last_commit: head,
            updated_at: new Date().toISOString(),
        },
        null,
        2,
    )}\n`,
    'utf8',
);

console.log(`[versionado] ${toSemver(currentVersion)} -> ${nextVersion} (${bumpType})`);
