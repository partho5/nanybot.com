import { Link, usePage } from '@inertiajs/react';
import {
    ExternalLink,
    FileText,
    FolderTree,
    KeyRound,
    Tags,
    UserCircle,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { index as apiKeys } from '@/routes/admin/api-keys';
import { index as articles } from '@/routes/admin/articles';
import { index as categories } from '@/routes/admin/categories';
import { index as tags } from '@/routes/admin/tags';
import { edit as editUser, index as users } from '@/routes/admin/users';
import { index as blog } from '@/routes/blog';
import type { NavItem } from '@/types';

const footerNavItems: NavItem[] = [
    {
        title: 'View blog',
        href: blog(),
        icon: ExternalLink,
    },
];

export function AppSidebar() {
    const { auth } = usePage().props;

    const myProfile: NavItem = {
        title: 'My profile',
        href: editUser(auth.user.id),
        icon: UserCircle,
    };

    const mainNavItems: NavItem[] = auth.user.is_admin
        ? [
              { title: 'Articles', href: articles(), icon: FileText },
              { title: 'Categories', href: categories(), icon: FolderTree },
              { title: 'Tags', href: tags(), icon: Tags },
              { title: 'Authors', href: users(), icon: Users },
              myProfile,
              { title: 'API Keys', href: apiKeys(), icon: KeyRound },
          ]
        : [{ title: 'Articles', href: articles(), icon: FileText }, myProfile];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={articles()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
