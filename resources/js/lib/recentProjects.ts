const STORAGE_KEY = 'workflow_recent_projects_v1';

export type RecentProject = {
    id: number;
    name: string;
    code: string | null;
    ts: number;
};

export function addRecentProject(p: {
    id: number;
    name: string;
    code: string | null;
}): void {
    if (typeof window === 'undefined') {
        return;
    }
    let list: RecentProject[] = [];
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (raw) {
            list = JSON.parse(raw) as RecentProject[];
        }
    } catch {
        list = [];
    }
    list = list.filter((x) => x.id !== p.id);
    list.unshift({
        id: p.id,
        name: p.name,
        code: p.code,
        ts: Date.now(),
    });
    list = list.slice(0, 8);
    localStorage.setItem(STORAGE_KEY, JSON.stringify(list));
}

export function getRecentProjects(): RecentProject[] {
    if (typeof window === 'undefined') {
        return [];
    }
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) {
            return [];
        }
        return JSON.parse(raw) as RecentProject[];
    } catch {
        return [];
    }
}
