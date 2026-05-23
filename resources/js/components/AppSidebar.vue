<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    BarChart3,
    FileText,
    LayoutDashboard,
    List,
    Send,
    Tag,
    Upload,
    UserCog,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

const page = usePage();
const role = computed(() => (page.props.auth as any)?.user?.role ?? 'staff');

const mainNavItems = computed<NavItem[]>(() => [
    { title: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
    { title: 'Contacts', href: '/contacts', icon: Users },
    { title: 'Lists', href: '/lists', icon: List },
    { title: 'Tags', href: '/tags', icon: Tag },
    { title: 'Imports', href: '/imports', icon: Upload },
    { title: 'Email Templates', href: '/templates', icon: FileText },
    { title: 'Campaigns', href: '/campaigns', icon: Send },
    { title: 'Reports', href: '/reports', icon: BarChart3 },
    { title: 'Activity Logs', href: '/activity-logs', icon: Activity },
]);

const bottomNavItems = computed<NavItem[]>(() => [
    ...(role.value === 'owner' || role.value === 'admin'
        ? [{ title: 'Users', href: '/users', icon: UserCog }]
        : []),
]);
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavMain :items="bottomNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
