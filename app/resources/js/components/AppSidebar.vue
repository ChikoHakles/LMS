<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import NavUser from '@/components/NavUser.vue';
import { SheetTitle } from '@/components/ui/sheet';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { normalizeRuangRole, roleLabels, roleNavigation, type RuangNavigationItem } from '@/navigation/role-navigation';
import type { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage<SharedData>();
const { isMobile, setOpenMobile } = useSidebar();
const role = computed(() => normalizeRuangRole(page.props.auth.user?.role));
const roleLabel = computed(() => roleLabels[role.value]);
const navigation = computed(() => roleNavigation[role.value]);

function itemHref(item: RuangNavigationItem): string {
    if (item.routeName && route().has(item.routeName)) return route(item.routeName);
    return route('workspace.placeholder', { page: item.fallbackPage });
}

function isItemActive(item: RuangNavigationItem): boolean {
    if (item.children) return item.children.some(isItemActive);

    const targetPath = new URL(itemHref(item), 'https://ruang.invalid').pathname;
    return page.url.split('?')[0] === targetPath;
}

function closeMobileNavigation() {
    setOpenMobile(false);
}
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="border-r-0">
        <SheetTitle v-if="isMobile" class="sr-only">Navigasi Ruang</SheetTitle>
        <SidebarHeader class="border-b border-sidebar-border px-4 py-4">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')" aria-label="Beranda Ruang" @click="closeMobileNavigation">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="px-2 py-3">
            <nav id="ruang-primary-navigation" aria-label="Navigasi utama">
                <SidebarGroup class="p-0">
                    <SidebarGroupLabel class="px-3 text-xs uppercase tracking-[0.16em]">Menu utama</SidebarGroupLabel>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in navigation" :key="item.title">
                            <template v-if="item.children">
                                <div class="flex items-center gap-2 px-3 py-2 text-sm font-semibold text-sidebar-foreground/80 group-data-[collapsible=icon]:hidden">
                                    <component :is="item.icon" class="size-4 shrink-0" aria-hidden="true" />
                                    <span>{{ item.title }}</span>
                                </div>
                                <SidebarMenuSub>
                                    <SidebarMenuSubItem v-for="child in item.children" :key="child.title">
                                        <SidebarMenuSubButton as-child :is-active="isItemActive(child)">
                                            <Link :href="itemHref(child)" :aria-current="isItemActive(child) ? 'page' : undefined" @click="closeMobileNavigation">
                                                <component :is="child.icon" aria-hidden="true" />
                                                <span>{{ child.title }}</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                </SidebarMenuSub>
                            </template>
                            <SidebarMenuButton v-else as-child :is-active="isItemActive(item)" :tooltip="item.title">
                                <Link :href="itemHref(item)" :aria-current="isItemActive(item) ? 'page' : undefined" @click="closeMobileNavigation">
                                    <component :is="item.icon" aria-hidden="true" />
                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroup>
            </nav>
        </SidebarContent>

        <SidebarFooter class="border-t border-sidebar-border px-3 py-3">
            <div class="mb-1 px-2 text-xs font-medium uppercase tracking-[0.12em] text-sidebar-foreground/70 group-data-[collapsible=icon]:hidden">{{ roleLabel }}</div>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
</template>

