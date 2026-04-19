export type TaskListPerson = {
    id: number;
    name: string;
    avatar?: string | null;
};

export type TaskListRow = {
    id: number;
    /** Proyecto dueño (vista cartera / varios proyectos). */
    project_id?: number;
    title: string;
    description: string | null;
    status: string;
    due_date: string | null;
    task_group_id: number;
    assignee: TaskListPerson | null;
    collaborators: TaskListPerson[];
};

export type TaskListGroup = {
    id: number;
    name: string;
    color: string;
    position: number;
    progress_percent: number;
    /** Presente en vista cartera: segmento pertenece a este proyecto. */
    project_id?: number;
};
