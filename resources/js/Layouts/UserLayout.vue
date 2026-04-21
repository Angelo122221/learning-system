<script setup>
import AppFlashBanner from '@/Components/AppFlashBanner.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const DESKTOP_BREAKPOINT = 1024;

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const flashSuccess = computed(() => page.props.flash?.success ?? '');
const flashError = computed(() => page.props.flash?.error ?? '');
const showingNavigationDropdown = ref(false);
const mobileExpandedMenu = ref(null);
const desktopOpenMenu = ref(null);
const headerNavRef = ref(null);
const desktopCloseHandle = ref(null);
const desktopMenuButtonRefs = ref({});
const supportEmail = 'cid.ozamiz@depedozamiz.net';
const emailCopied = ref(false);
const copyResetHandle = ref(null);

const govphLinks = [
    { label: 'GOV.PH', href: 'https://www.gov.ph/' },
    { label: 'Open Data Portal', href: 'https://data.gov.ph/' },
    { label: 'Official Gazette', href: 'https://www.officialgazette.gov.ph/' },
];

const governmentLinks = [
    { label: 'Office of the President', href: 'https://op-proper.gov.ph/' },
    { label: 'Office of the Vice President', href: 'https://ovp.gov.ph/' },
    { label: 'Senate of the Philippines', href: 'https://legacy.senate.gov.ph/' },
    { label: 'House of Representatives', href: 'https://www.congress.gov.ph/' },
    { label: 'Supreme Court', href: 'https://sc.judiciary.gov.ph/' },
    { label: 'Court of Appeals', href: 'https://ca.judiciary.gov.ph/' },
    { label: 'Sandiganbayan', href: 'https://sb.judiciary.gov.ph/' },
];

const depedSystemsLinks = [
    {
        title: 'Enhanced Basic Education Information System (EBEIS)',
        href: 'https://ebeis.deped.gov.ph/beis/',
        description: 'School data and reporting system',
    },
    {
        title: 'Learner Information System (LIS)',
        href: 'https://lis.deped.gov.ph/uis/login',
        description: 'Learner records and enrollment management',
    },
    {
        title: 'Learning Resource Management and Development System (LRMDS)',
        href: 'https://lrmds.deped.gov.ph/',
        description: 'Teaching and learning resource portal',
    },
    {
        title: 'DepEd Partnership Database System (DPDS)',
        href: 'https://partnershipsdatabase.deped.gov.ph/#',
        description: 'School partnership and support tracking',
    },
];

const officialLinks = [
    {
        label: 'Transparency Seal',
        href: 'https://ozamiz.deped.gov.ph/transparency/',
        logo: '/images/official-links/transparency-seal-logo.png',
        logoAlt: 'Transparency Seal logo',
    },
    {
        label: 'Department of Education',
        href: 'https://www.deped.gov.ph/',
        logo: '/images/official-links/department-of-education-logo.png',
        logoAlt: 'Department of Education logo',
    },
    {
        label: 'DepEd Region X',
        href: 'https://deped10.com/',
        logo: '/images/official-links/deped-region-x-logo.png',
        logoAlt: 'DepEd Region X logo',
    },
    {
        label: 'DepEd Ozamiz',
        href: 'https://ozamiz.deped.gov.ph/',
        logo: '/images/official-links/deped-ozamiz-logo.png',
        logoAlt: 'DepEd Ozamiz logo',
    },
];

const resetCopiedState = () => {
    if (copyResetHandle.value) {
        clearTimeout(copyResetHandle.value);
        copyResetHandle.value = null;
    }
};

const clearDesktopCloseHandle = () => {
    if (desktopCloseHandle.value) {
        clearTimeout(desktopCloseHandle.value);
        desktopCloseHandle.value = null;
    }
};

const closeDesktopMenu = () => {
    clearDesktopCloseHandle();
    desktopOpenMenu.value = null;
};

const openDesktopMenu = (menuKey) => {
    clearDesktopCloseHandle();
    desktopOpenMenu.value = menuKey;
};

const scheduleDesktopMenuClose = () => {
    clearDesktopCloseHandle();
    desktopCloseHandle.value = window.setTimeout(() => {
        desktopOpenMenu.value = null;
        desktopCloseHandle.value = null;
    }, 140);
};

const fallbackCopySupportEmail = () => {
    const copySource = document.createElement('textarea');
    copySource.value = supportEmail;
    copySource.setAttribute('readonly', '');
    copySource.style.position = 'absolute';
    copySource.style.left = '-9999px';
    document.body.appendChild(copySource);
    copySource.select();
    document.execCommand('copy');
    document.body.removeChild(copySource);
};

const copySupportEmail = async () => {
    try {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(supportEmail);
        } else {
            fallbackCopySupportEmail();
        }

        emailCopied.value = true;
        resetCopiedState();
        copyResetHandle.value = setTimeout(() => {
            emailCopied.value = false;
            copyResetHandle.value = null;
        }, 2000);
    } catch {
        fallbackCopySupportEmail();
        emailCopied.value = true;
        resetCopiedState();
        copyResetHandle.value = setTimeout(() => {
            emailCopied.value = false;
            copyResetHandle.value = null;
        }, 2000);
    }
};

const getMenuPanelId = (menuKey, mode) => `${mode}-${menuKey}-panel`;
const isDesktopMenuOpen = (menuKey) => desktopOpenMenu.value === menuKey;
const isMobileMenuOpen = (menuKey) => mobileExpandedMenu.value === menuKey;

const setDesktopMenuButtonRef = (menuKey) => (element) => {
    if (element) {
        desktopMenuButtonRefs.value[menuKey] = element;
        return;
    }

    delete desktopMenuButtonRefs.value[menuKey];
};

const focusDesktopMenuButton = (menuKey) => {
    desktopMenuButtonRefs.value[menuKey]?.focus();
};

const focusFirstMenuLink = (menuKey, mode) => {
    const panel = document.getElementById(getMenuPanelId(menuKey, mode));
    const firstInteractiveElement = panel?.querySelector('a, button, [tabindex]:not([tabindex="-1"])');

    if (firstInteractiveElement instanceof HTMLElement) {
        firstInteractiveElement.focus();
    }
};

const toggleMobileNavigation = () => {
    showingNavigationDropdown.value = !showingNavigationDropdown.value;

    if (!showingNavigationDropdown.value) {
        mobileExpandedMenu.value = null;
    }

    closeDesktopMenu();
};

const toggleMobileMenuSection = (menuKey) => {
    mobileExpandedMenu.value = mobileExpandedMenu.value === menuKey ? null : menuKey;
};

const handleDesktopMenuFocusOut = (event) => {
    const nextTarget = event.relatedTarget;

    if (nextTarget instanceof Node && event.currentTarget instanceof HTMLElement && event.currentTarget.contains(nextTarget)) {
        return;
    }

    scheduleDesktopMenuClose();
};

const handleDesktopMenuButtonKeydown = (event, menuKey) => {
    if (event.key === 'Enter' || event.key === ' ' || event.key === 'ArrowDown') {
        event.preventDefault();
        openDesktopMenu(menuKey);
        requestAnimationFrame(() => focusFirstMenuLink(menuKey, 'desktop'));
        return;
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        closeDesktopMenu();
    }
};

const handleDesktopMenuPanelKeydown = (event, menuKey) => {
    if (event.key !== 'Escape') {
        return;
    }

    event.preventDefault();
    closeDesktopMenu();
    focusDesktopMenuButton(menuKey);
};

const handleGlobalPointerDown = (event) => {
    if (!(event.target instanceof Node)) {
        return;
    }

    if (headerNavRef.value?.contains(event.target)) {
        return;
    }

    closeDesktopMenu();
    showingNavigationDropdown.value = false;
    mobileExpandedMenu.value = null;
};

const handleWindowResize = () => {
    if (window.innerWidth >= DESKTOP_BREAKPOINT) {
        showingNavigationDropdown.value = false;
        mobileExpandedMenu.value = null;
        return;
    }

    closeDesktopMenu();
};

onMounted(() => {
    document.addEventListener('pointerdown', handleGlobalPointerDown);
    window.addEventListener('resize', handleWindowResize);
});

onBeforeUnmount(() => {
    resetCopiedState();
    clearDesktopCloseHandle();
    document.removeEventListener('pointerdown', handleGlobalPointerDown);
    window.removeEventListener('resize', handleWindowResize);
});
</script>

<template>
    <div class="user-portal-shell flex min-h-screen flex-col overflow-x-hidden bg-[#f5f6f8]">
        <header ref="headerNavRef" class="user-portal-header border-b border-slate-200 bg-white shadow-[0_14px_36px_rgba(15,23,42,0.08)]">
            <div class="bg-[#f28c28] text-white">
                <div class="mx-auto flex w-full max-w-[1440px] items-center justify-between gap-3 px-4 py-2 text-[11px] font-bold uppercase tracking-[0.18em] sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3 lg:gap-4">
                        <a
                            href="https://www.gov.ph/"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="transition hover:text-white/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#f28c28]"
                        >
                            GovPH
                        </a>

                        <nav
                            aria-label="Primary"
                            class="hidden lg:flex lg:items-center lg:gap-4"
                        >
                            <div
                                class="relative"
                                @mouseenter="openDesktopMenu('deped-systems')"
                                @mouseleave="scheduleDesktopMenuClose"
                                @focusin="openDesktopMenu('deped-systems')"
                                @focusout="handleDesktopMenuFocusOut"
                            >
                                <button
                                    :ref="setDesktopMenuButtonRef('deped-systems')"
                                    type="button"
                                    class="inline-flex items-center px-0 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-white transition hover:text-white/85 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#f28c28]"
                                    aria-haspopup="true"
                                    :aria-controls="getMenuPanelId('deped-systems', 'desktop')"
                                    :aria-expanded="isDesktopMenuOpen('deped-systems') ? 'true' : 'false'"
                                    @click="isDesktopMenuOpen('deped-systems') ? closeDesktopMenu() : openDesktopMenu('deped-systems')"
                                    @keydown="handleDesktopMenuButtonKeydown($event, 'deped-systems')"
                                >
                                    <span>DepEd Systems</span>
                                </button>

                                <transition
                                    enter-active-class="transition duration-180 ease-out"
                                    enter-from-class="translate-y-1 opacity-0"
                                    enter-to-class="translate-y-0 opacity-100"
                                    leave-active-class="transition duration-140 ease-in"
                                    leave-from-class="translate-y-0 opacity-100"
                                    leave-to-class="translate-y-1 opacity-0"
                                >
                                    <div
                                        v-show="isDesktopMenuOpen('deped-systems')"
                                        :id="getMenuPanelId('deped-systems', 'desktop')"
                                        class="absolute left-0 top-full z-50 mt-2 w-[22.5rem] rounded-[1rem] border border-slate-200 bg-white p-2 text-slate-900 shadow-[0_20px_44px_rgba(15,23,42,0.22)]"
                                        @mouseenter="openDesktopMenu('deped-systems')"
                                        @mouseleave="scheduleDesktopMenuClose"
                                        @keydown="handleDesktopMenuPanelKeydown($event, 'deped-systems')"
                                    >
                                        <p class="px-2.5 pb-1.5 text-[10px] font-black uppercase tracking-[0.16em] text-blue-700">External systems</p>
                                        <div class="space-y-0.5">
                                            <a
                                                v-for="system in depedSystemsLinks"
                                                :key="system.href"
                                                :href="system.href"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="group block rounded-[0.85rem] px-2.5 py-2 transition hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                                            >
                                                <span class="block min-w-0">
                                                    <span class="block text-[13px] font-black leading-[1.2rem] text-slate-900 transition group-hover:text-blue-700">
                                                        {{ system.title }}
                                                    </span>
                                                </span>
                                            </a>
                                        </div>
                                    </div>
                                </transition>
                            </div>

                            <div
                                class="relative"
                                @mouseenter="openDesktopMenu('official-links')"
                                @mouseleave="scheduleDesktopMenuClose"
                                @focusin="openDesktopMenu('official-links')"
                                @focusout="handleDesktopMenuFocusOut"
                            >
                                <button
                                    :ref="setDesktopMenuButtonRef('official-links')"
                                    type="button"
                                    class="inline-flex items-center px-0 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-white transition hover:text-white/85 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#f28c28]"
                                    aria-haspopup="true"
                                    :aria-controls="getMenuPanelId('official-links', 'desktop')"
                                    :aria-expanded="isDesktopMenuOpen('official-links') ? 'true' : 'false'"
                                    @click="isDesktopMenuOpen('official-links') ? closeDesktopMenu() : openDesktopMenu('official-links')"
                                    @keydown="handleDesktopMenuButtonKeydown($event, 'official-links')"
                                >
                                    <span>Official Links</span>
                                </button>

                                <transition
                                    enter-active-class="transition duration-180 ease-out"
                                    enter-from-class="translate-y-1 opacity-0"
                                    enter-to-class="translate-y-0 opacity-100"
                                    leave-active-class="transition duration-140 ease-in"
                                    leave-from-class="translate-y-0 opacity-100"
                                    leave-to-class="translate-y-1 opacity-0"
                                >
                                    <div
                                        v-show="isDesktopMenuOpen('official-links')"
                                        :id="getMenuPanelId('official-links', 'desktop')"
                                        class="absolute left-0 top-full z-50 mt-2 w-[18.5rem] rounded-[1rem] border border-slate-200 bg-white p-2 text-slate-900 shadow-[0_20px_44px_rgba(15,23,42,0.22)]"
                                        @mouseenter="openDesktopMenu('official-links')"
                                        @mouseleave="scheduleDesktopMenuClose"
                                        @keydown="handleDesktopMenuPanelKeydown($event, 'official-links')"
                                    >
                                        <p class="px-2.5 pb-1.5 text-[10px] font-black uppercase tracking-[0.16em] text-blue-700">Quick links</p>
                                        <!-- Replace these placeholder artwork files with official logos when approved assets are available. -->
                                        <div class="space-y-0.5">
                                            <a
                                                v-for="link in officialLinks"
                                                :key="link.href"
                                                :href="link.href"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="group flex items-center gap-2.5 rounded-[0.85rem] px-2.5 py-2 transition hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                                            >
                                                <img
                                                    :src="link.logo"
                                                    :alt="link.logoAlt"
                                                    class="h-8 w-8 shrink-0 object-contain"
                                                />
                                                <span class="min-w-0 flex-1 text-[13px] font-black leading-[1.1rem] text-slate-900 transition group-hover:text-blue-700">
                                                    {{ link.label }}
                                                </span>
                                            </a>
                                        </div>
                                    </div>
                                </transition>
                            </div>
                        </nav>
                    </div>

                    <div class="flex items-center gap-2">
                        <Link
                            href="/materials"
                            class="rounded-full border border-white/45 bg-white/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.18em] text-white transition hover:bg-white hover:text-[#183f95] focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#f28c28]"
                        >
                            Materials Inventory
                        </Link>
                        <Link
                            href="/profile"
                            class="rounded-full border border-white/45 bg-white/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.18em] text-white transition hover:bg-white hover:text-[#183f95] focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#f28c28]"
                        >
                            Teacher Profile
                        </Link>
                        <Link href="/logout" method="post" as="button" class="rounded-full border border-white/45 bg-white/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.18em] text-white transition hover:bg-white hover:text-[#183f95] focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#f28c28]">
                            Log Out
                        </Link>
                    </div>
                </div>
            </div>

            <div class="bg-[linear-gradient(90deg,#0f4ba8_0%,#214dc1_38%,#3853ba_72%,#183f95_100%)] text-white">
                <div class="mx-auto flex w-full max-w-[1440px] flex-col gap-4 px-4 py-4 sm:px-6 lg:px-8 lg:py-5">
                    <div class="flex items-start justify-between gap-4">
                        <Link href="/resources" class="flex min-w-0 items-start gap-3 sm:gap-4">
                            <img
                                src="/images/crystal-login-logo.png"
                                alt="CRYSTAL Portal official logo"
                                class="mt-2 h-auto w-14 shrink-0 object-contain sm:w-16"
                            />
                            <div class="min-w-0 pt-1">
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-white/75 sm:text-[11px]">
                                    Republic of the Philippines
                                </p>
                                <h1 class="truncate text-lg font-black tracking-tight sm:text-2xl">
                                    DepEd Ozamiz - CRYSTAL Portal
                                </h1>
                                <p class="mt-1 max-w-3xl text-xs font-medium leading-5 text-white/82 sm:text-sm">
                                    Complete Resources for Year-Round Systematized Teaching and Learning
                                </p>
                            </div>
                        </Link>

                        <button
                            type="button"
                            class="rounded-xl border border-white/35 bg-white/10 px-3 py-2 text-xs font-black uppercase tracking-[0.18em] text-white transition hover:bg-white/16 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#214dc1] lg:hidden"
                            aria-controls="mobile-primary-navigation"
                            :aria-expanded="showingNavigationDropdown ? 'true' : 'false'"
                            @click="toggleMobileNavigation"
                        >
                            Menu
                        </button>
                    </div>

                    <div v-if="showingNavigationDropdown" id="mobile-primary-navigation" class="space-y-3 lg:hidden">
                        <Link
                            href="/materials"
                            class="inline-flex w-fit items-center rounded-full border border-white/35 bg-white/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.16em] text-white transition hover:bg-white hover:text-[#183f95] focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#214dc1]"
                        >
                            Materials Inventory
                        </Link>

                        <Link
                            href="/profile"
                            class="inline-flex w-fit items-center rounded-full border border-white/35 bg-white/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.16em] text-white transition hover:bg-white hover:text-[#183f95] focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#214dc1]"
                        >
                            Teacher Profile
                        </Link>

                        <div
                            v-if="user"
                            class="rounded-[1.25rem] border border-white/18 bg-white/8 px-4 py-4 backdrop-blur"
                        >
                            <p class="truncate text-sm font-black text-white">{{ user.name }}</p>
                            <p class="truncate text-[11px] font-semibold uppercase tracking-[0.14em] text-white/68">{{ user.email }}</p>
                        </div>

                        <nav
                            aria-label="Primary"
                            class="overflow-hidden rounded-[1rem] border border-white/24 bg-white/16 shadow-[0_14px_28px_rgba(8,35,97,0.16)] backdrop-blur-md"
                        >
                            <div class="border-b border-white/10 last:border-b-0">
                                <button
                                    type="button"
                                    class="flex w-full items-center px-4 py-3 text-left text-[13px] font-black tracking-[0.04em] text-white transition hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-inset"
                                    :aria-controls="getMenuPanelId('deped-systems', 'mobile')"
                                    :aria-expanded="isMobileMenuOpen('deped-systems') ? 'true' : 'false'"
                                    @click="toggleMobileMenuSection('deped-systems')"
                                >
                                    <span>DepEd Systems</span>
                                </button>

                                <div
                                    v-show="isMobileMenuOpen('deped-systems')"
                                    :id="getMenuPanelId('deped-systems', 'mobile')"
                                    class="space-y-0.5 px-3 pb-3"
                                >
                                    <a
                                        v-for="system in depedSystemsLinks"
                                        :key="system.href"
                                        :href="system.href"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="block rounded-[0.85rem] px-3 py-2 transition hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                    >
                                        <span class="block text-[13px] font-black leading-[1.2rem] text-white">{{ system.title }}</span>
                                    </a>
                                </div>
                            </div>

                            <div class="last:border-b-0">
                                <button
                                    type="button"
                                    class="flex w-full items-center px-4 py-3 text-left text-[13px] font-black tracking-[0.04em] text-white transition hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-inset"
                                    :aria-controls="getMenuPanelId('official-links', 'mobile')"
                                    :aria-expanded="isMobileMenuOpen('official-links') ? 'true' : 'false'"
                                    @click="toggleMobileMenuSection('official-links')"
                                >
                                    <span>Official Links</span>
                                </button>

                                <div
                                    v-show="isMobileMenuOpen('official-links')"
                                    :id="getMenuPanelId('official-links', 'mobile')"
                                    class="space-y-0.5 px-3 pb-3"
                                >
                                    <!-- Replace these placeholder artwork files with official logos when approved assets are available. -->
                                    <a
                                        v-for="link in officialLinks"
                                        :key="link.href"
                                        :href="link.href"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex items-center gap-2.5 rounded-[0.85rem] px-3 py-2 transition hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                    >
                                        <img
                                            :src="link.logo"
                                            :alt="link.logoAlt"
                                            class="h-8 w-8 shrink-0 object-contain"
                                        />
                                        <span class="min-w-0 flex-1 text-[13px] font-black leading-[1.1rem] text-white">
                                            {{ link.label }}
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-200/75 bg-[linear-gradient(180deg,#f9fbff_0%,#f2f6fc_100%)]">
                <div class="mx-auto flex w-full max-w-[1440px] px-4 py-1.5 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-600 ring-1 ring-blue-100">
                            <svg
                                viewBox="0 0 20 20"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-3.5 w-3.5"
                                aria-hidden="true"
                            >
                                <path
                                    d="M3.333 5.833h13.334A1.667 1.667 0 0 1 18.333 7.5v5A1.667 1.667 0 0 1 16.667 14.167H3.333A1.667 1.667 0 0 1 1.667 12.5v-5A1.667 1.667 0 0 1 3.333 5.833Z"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <path
                                    d="m2.083 6.667 6.936 4.855a1.667 1.667 0 0 0 1.962 0l6.936-4.855"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </span>

                        <button
                            type="button"
                            class="group flex min-w-0 items-center gap-2 rounded-full text-sm font-semibold text-slate-700 transition hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                            @click="copySupportEmail"
                        >
                            <span class="truncate underline decoration-slate-300 underline-offset-4 transition group-hover:decoration-blue-400">
                                {{ supportEmail }}
                            </span>
                            <svg
                                viewBox="0 0 20 20"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-3.5 w-3.5 shrink-0 text-slate-400 transition group-hover:text-blue-500"
                                aria-hidden="true"
                            >
                                <path
                                    d="M7.5 6.667A1.667 1.667 0 0 1 9.167 5h5A1.667 1.667 0 0 1 15.833 6.667v6.666A1.667 1.667 0 0 1 14.167 15h-5A1.667 1.667 0 0 1 7.5 13.333V6.667Z"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <path
                                    d="M5.833 11.667H5A1.667 1.667 0 0 1 3.333 10V5A1.667 1.667 0 0 1 5 3.333h5A1.667 1.667 0 0 1 11.667 5v.833"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </button>

                        <p
                            v-if="emailCopied"
                            class="shrink-0 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-black uppercase tracking-[0.16em] text-emerald-700 ring-1 ring-emerald-100"
                            aria-live="polite"
                        >
                            Email copied
                        </p>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-[1440px] flex-1 px-4 py-6 sm:px-6 md:py-8 lg:px-8">
            <AppFlashBanner tone="success" :message="flashSuccess" />
            <AppFlashBanner tone="error" :message="flashError" />
            <slot />
        </main>

        <footer class="mt-8 border-t border-slate-200/80 bg-[linear-gradient(180deg,#eef3f8_0%,#e7edf5_100%)] text-slate-700">
            <div class="mx-auto w-full max-w-[1440px] px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-[1160px] py-4 sm:py-5 lg:py-5">
                    <div class="grid gap-4 sm:gap-5 md:grid-cols-[minmax(0,1.16fr)_minmax(0,0.92fr)_minmax(0,0.92fr)] md:gap-5 lg:gap-6">
                        <section class="flex items-start gap-2.5 lg:gap-3">
                            <img
                                src="/images/govph-seal-footer.webp"
                                alt=""
                                class="mt-0.5 w-20 shrink-0 sm:w-24 lg:w-28"
                                aria-hidden="true"
                            />
                            <div class="min-w-0">
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-blue-500">Republic of the Philippines</p>
                                <p class="mt-1.5 text-[11px] leading-[1.25rem] text-slate-600">
                                    All content is in the public domain unless otherwise stated.
                                </p>
                            </div>
                        </section>

                        <section>
                            <h3 class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">About GOVPH</h3>
                            <p class="mt-1.5 text-[11px] leading-[1.25rem] text-slate-600">
                                Learn more about the Philippine government, its structure, how government works, and the people behind it.
                            </p>
                            <div class="mt-2 flex flex-col gap-0.5">
                                <a
                                    v-for="link in govphLinks"
                                    :key="link.href"
                                    :href="link.href"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex w-fit items-center gap-1 text-[11px] font-semibold leading-[1.2rem] text-slate-700 transition hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-offset-2"
                                >
                                    <span>{{ link.label }}</span>
                                    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" aria-hidden="true">
                                        <path d="M7.5 12.5 12.5 7.5M8.333 7.5H12.5v4.167" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </a>
                            </div>
                        </section>

                        <section>
                            <h3 class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">Government Links</h3>
                            <div class="mt-1.5 grid gap-0.5">
                                <a
                                    v-for="link in governmentLinks"
                                    :key="link.href"
                                    :href="link.href"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="w-fit text-[11px] font-semibold leading-[1.2rem] text-slate-700 transition hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-offset-2"
                                >
                                    {{ link.label }}
                                </a>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
