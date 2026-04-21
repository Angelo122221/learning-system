<script setup>
import ProductLayout from '@/Layouts/ProductLayout.vue';
import { Link } from '@inertiajs/vue3';

const navItems = [
    { label: 'Resources', href: '/admin/resources', active: 'admin.resources' },
    { label: 'Announcements', href: '/admin/announcements', active: 'admin.announcements' },
    { label: 'Carousel', href: '/admin/carousel', active: 'admin.carousel' },
    { label: 'Videos', href: '/admin/videos', active: 'admin.videos' },
    { label: 'Analytics', href: '/admin/analytics', active: 'admin.analytics' },
    { label: 'Materials Inventory', href: '/admin/materials-inventory', active: 'admin.materials.inventory' },
    { label: 'Users', href: '/admin/users', active: 'admin.users' },
];
</script>

<template>
    <ProductLayout
        :nav-items="navItems"
        home-href="/admin/resources"
        brand-label="Learning Resources"
        brand-caption="Admin Control Center"
    >
        <template #nav-actions>
            <Link href="/logout" method="post" as="button" class="action-btn-danger">
                Log Out
            </Link>
        </template>
        <slot />
    </ProductLayout>
</template>
