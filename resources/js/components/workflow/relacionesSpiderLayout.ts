/**
 * Layout tipo telaraña: anillo inicial mezclando personas/proyectos,
 * simulación de fuerzas y pasadas de separación para evitar solapes entre cards.
 */

export type SpiderEdge = {
    user_id: number;
    project_id: number;
};

export const LAYOUT_NODE_W = 148;
/** Alto de card asumido (2 líneas + padding) — debe alinearse con estilos en RelacionesFlow. */
export const LAYOUT_NODE_H = 76;

type Body = {
    x: number;
    y: number;
    vx: number;
    vy: number;
};

const BASE_ITERATIONS = 160;
/** Repulsión entre pares (centros). */
const REPULSE = 7800;
const SPRING = 0.088;
const IDEAL_LINK = 92;
const DAMPING = 0.87;
const CENTER_PULL = 0.01;
/** Margen extra entre cajas tras simulación (px lógicos). */
const BOX_MARGIN = 10;

function clampDist(dx: number, dy: number, minD: number): { dx: number; dy: number } {
    const d = Math.hypot(dx, dy);
    if (d < minD && d > 1e-6) {
        const s = minD / d;
        return { dx: dx * s, dy: dy * s };
    }
    return { dx, dy };
}

/** Mínima distancia entre centros para que dos rectángulos WxH no se solapen (peor caso diagonal). */
function minCenterSeparation(w: number, h: number, margin: number): number {
    return Math.hypot(w + margin, h + margin) * 0.98;
}

/**
 * Separa rectángulos del mismo tamaño (posición = centro del nodo).
 */
function resolveAABBCollisions(
    centers: { x: number; y: number }[],
    w: number,
    h: number,
    margin: number,
    iterations: number,
): void {
    const effW = w + margin;
    const effH = h + margin;
    const n = centers.length;
    for (let iter = 0; iter < iterations; iter++) {
        for (let i = 0; i < n; i++) {
            for (let j = i + 1; j < n; j++) {
                const dx = centers[j].x - centers[i].x;
                const dy = centers[j].y - centers[i].y;
                const ox = effW - Math.abs(dx);
                const oy = effH - Math.abs(dy);
                if (ox > 0 && oy > 0) {
                    if (ox < oy) {
                        const push = ox * 0.52 + 1;
                        const dir = dx >= 0 ? 1 : -1;
                        centers[i].x -= dir * push;
                        centers[j].x += dir * push;
                    } else {
                        const push = oy * 0.52 + 1;
                        const dir = dy >= 0 ? 1 : -1;
                        centers[i].y -= dir * push;
                        centers[j].y += dir * push;
                    }
                }
            }
        }
    }
}

export function computeSpiderLayout(
    userIds: number[],
    projectIds: number[],
    graphEdges: SpiderEdge[],
    opts: {
        canvasW: number;
        canvasH: number;
        padding: number;
        nodeW: number;
        nodeH: number;
    },
): {
    users: Map<number, { x: number; y: number }>;
    projects: Map<number, { x: number; y: number }>;
} {
    const nodeW = opts.nodeW;
    const nodeH = opts.nodeH;
    const users = new Map<number, { x: number; y: number }>();
    const projects = new Map<number, { x: number; y: number }>();

    const nu = userIds.length;
    const np = projectIds.length;

    if (nu === 0 && np === 0) {
        return { users, projects };
    }

    const order: { kind: 'u' | 'p'; id: number }[] = [];
    let iu = 0;
    let ip = 0;
    while (iu < nu || ip < np) {
        if (iu < nu) {
            order.push({ kind: 'u', id: userIds[iu] });
            iu += 1;
        }
        if (ip < np) {
            order.push({ kind: 'p', id: projectIds[ip] });
            ip += 1;
        }
    }

    const n = order.length;
    const bodies: Body[] = [];
    const kind: ('u' | 'p')[] = [];
    const ids: number[] = [];

    const seed = graphEdges.length * 17 + nu * 31 + np * 13;
    const minSep = minCenterSeparation(nodeW, nodeH, BOX_MARGIN);
    const R = Math.max(
        minSep * 0.85,
        Math.min(opts.canvasW, opts.canvasH) * 0.32,
    );

    for (let i = 0; i < n; i++) {
        const jitter = Math.sin(seed + i * 12.9898) * 0.06;
        const a = (2 * Math.PI * i) / n + jitter + 0.25;
        bodies.push({
            x: Math.cos(a) * R,
            y: Math.sin(a) * R,
            vx: 0,
            vy: 0,
        });
        kind.push(order[i].kind);
        ids.push(order[i].id);
    }

    const indexOfUser = new Map(userIds.map((id, i) => [id, i] as const));
    const indexOfProject = new Map(
        projectIds.map((id, i) => [id, nu + i] as const),
    );

    const links: { i: number; j: number }[] = [];
    for (const e of graphEdges) {
        const ui = indexOfUser.get(e.user_id);
        const pj = indexOfProject.get(e.project_id);
        if (ui === undefined || pj === undefined) {
            continue;
        }
        links.push({ i: ui, j: pj });
    }

    const iterations = Math.min(
        260,
        BASE_ITERATIONS + Math.round(n * 4),
    );

    for (let iter = 0; iter < iterations; iter++) {
        const fx = new Array(n).fill(0);
        const fy = new Array(n).fill(0);

        for (let i = 0; i < n; i++) {
            for (let j = i + 1; j < n; j++) {
                let dx = bodies[j].x - bodies[i].x;
                let dy = bodies[j].y - bodies[i].y;
                let d = Math.hypot(dx, dy);
                if (d < 1e-4) {
                    d = 1e-4;
                    dx = 0.01;
                    dy = 0;
                }
                /** Repulsión + empuje duro si están demasiado cerca (evita solape). */
                let rep = REPULSE / (d * d);
                if (d < minSep) {
                    rep += (minSep - d) * 2.8;
                }
                const nx = (dx / d) * rep;
                const ny = (dy / d) * rep;
                fx[i] -= nx;
                fy[i] -= ny;
                fx[j] += nx;
                fy[j] += ny;
            }
        }

        for (const { i, j } of links) {
            let dx = bodies[j].x - bodies[i].x;
            let dy = bodies[j].y - bodies[i].y;
            const { dx: rdx, dy: rdy } = clampDist(dx, dy, minSep * 0.45);
            dx = rdx;
            dy = rdy;
            const d = Math.hypot(dx, dy);
            const diff = d - IDEAL_LINK;
            const f = SPRING * diff;
            if (d < 1e-6) {
                continue;
            }
            const nx = (dx / d) * f;
            const ny = (dy / d) * f;
            fx[i] += nx;
            fy[i] += ny;
            fx[j] -= nx;
            fy[j] -= ny;
        }

        for (let i = 0; i < n; i++) {
            fx[i] -= CENTER_PULL * bodies[i].x;
            fy[i] -= CENTER_PULL * bodies[i].y;
        }

        for (let i = 0; i < n; i++) {
            bodies[i].vx = (bodies[i].vx + fx[i]) * DAMPING;
            bodies[i].vy = (bodies[i].vy + fy[i]) * DAMPING;
            bodies[i].x += bodies[i].vx;
            bodies[i].y += bodies[i].vy;
        }
    }

    const centers = bodies.map((b) => ({ x: b.x, y: b.y }));

    let minX = Infinity;
    let minY = Infinity;
    let maxX = -Infinity;
    let maxY = -Infinity;
    for (const c of centers) {
        minX = Math.min(minX, c.x);
        minY = Math.min(minY, c.y);
        maxX = Math.max(maxX, c.x);
        maxY = Math.max(maxY, c.y);
    }
    const bw = Math.max(maxX - minX, minSep);
    const bh = Math.max(maxY - minY, minSep);
    const innerW = opts.canvasW - opts.padding * 2;
    const innerH = opts.canvasH - opts.padding * 2;
    const scale = Math.min(innerW / bw, innerH / bh, 1.45) * 0.9;
    const cx = (minX + maxX) / 2;
    const cy = (minY + maxY) / 2;
    const ox = opts.padding + innerW / 2;
    const oy = opts.padding + innerH / 2;

    const scaled = centers.map((c) => ({
        x: (c.x - cx) * scale + ox,
        y: (c.y - cy) * scale + oy,
    }));

    /** En coordenadas de pantalla (cards con ancho/alto fijos en px). */
    resolveAABBCollisions(scaled, nodeW, nodeH, BOX_MARGIN, 100);
    resolveAABBCollisions(scaled, nodeW, nodeH, BOX_MARGIN, 45);

    for (let i = 0; i < n; i++) {
        const topLeft = {
            x: scaled[i].x - nodeW / 2,
            y: scaled[i].y - nodeH / 2,
        };
        if (kind[i] === 'u') {
            users.set(ids[i], topLeft);
        } else {
            projects.set(ids[i], topLeft);
        }
    }

    return { users, projects };
}
