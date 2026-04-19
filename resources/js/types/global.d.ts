import type { Auth } from '@/types/auth';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        readonly VITE_REVERB_APP_KEY?: string;
        readonly VITE_REVERB_HOST?: string;
        readonly VITE_REVERB_PORT?: string;
        readonly VITE_REVERB_SCHEME?: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            appVersion: string;
            cfrdDomain: string;
            workflowRealtimeEnabled: boolean;
            unread_notifications_count: number;
            auth: Auth;
            sidebarOpen: boolean;
            sidebarProjects: {
                id: number;
                name: string;
                code: string | null;
                status: string;
            }[];
            sidebarCanCreateProject: boolean;
            [key: string]: unknown;
        };
    }
}

declare module 'vue' {
    interface ComponentCustomProperties {
        $inertia: typeof Router;
        $page: Page;
        $headManager: ReturnType<typeof createHeadManager>;
    }
}
