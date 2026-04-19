declare module 'frappe-gantt' {
    type GanttTask = {
        id: string;
        name: string;
        start: string;
        end: string;
        progress: number;
        dependencies?: string;
    };

    type GanttOptions = Record<string, unknown>;

    export default class Gantt {
        public constructor(
            wrapper: string | HTMLElement | SVGElement,
            tasks: GanttTask[],
            options?: GanttOptions,
        );

        public clear(): void;
    }
}
