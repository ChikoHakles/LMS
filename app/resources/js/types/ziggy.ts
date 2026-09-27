import type { RouteParams } from 'ziggy-js';

declare global {
    interface RouteHelper {
        has(name: string): boolean;
    }

    function route(): RouteHelper;
    function route(name: string, params?: RouteParams<typeof name> | undefined, absolute?: boolean): string;
}

declare module '@vue/runtime-core' {
    interface ComponentCustomProperties {
        route: typeof route;
    }
}
