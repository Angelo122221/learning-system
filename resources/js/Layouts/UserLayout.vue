<script setup>
import AppFlashBanner from '@/Components/AppFlashBanner.vue';
import { asset, withBasePath } from '@/lib/basePath';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const isAuthenticated = computed(() => Boolean(user.value));
const flashSuccess = computed(() => page.props.flash?.success ?? '');
const flashError = computed(() => page.props.flash?.error ?? '');
const showingNavigationDropdown = ref(false);
const activeMobileSection = ref(null);
const activeHeaderDropdown = ref(null);
const supportEmail = 'cid.ozamiz@depedozamiz.net';
const emailCopied = ref(false);
const copyResetHandle = ref(null);
const headerDropdownCloseHandle = ref(null);
const loginPath = withBasePath('/login');
const philippineStandardTime = ref('');
let philippineStandardTimeHandle = null;

const govphLinks = [
    { label: 'GOV.PH', href: 'https://www.gov.ph/' },
    { label: 'Open Data Portal', href: 'https://data.gov.ph/' },
    { label: 'Official Gazette', href: 'https://www.officialgazette.gov.ph/' },
];

const philippineStandardTimeFormatter = new Intl.DateTimeFormat('en-US', {
    timeZone: 'Asia/Manila',
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    second: '2-digit',
    hour12: true,
});

const updatePhilippineStandardTime = () => {
    philippineStandardTime.value = philippineStandardTimeFormatter.format(new Date());
};

const governmentLinks = [
    { label: 'Office of the President', href: 'https://op-proper.gov.ph/' },
    { label: 'Office of the Vice President', href: 'https://ovp.gov.ph/' },
    { label: 'Senate of the Philippines', href: 'https://legacy.senate.gov.ph/' },
    { label: 'House of Representatives', href: 'https://www.congress.gov.ph/' },
    { label: 'Supreme Court', href: 'https://sc.judiciary.gov.ph/' },
    { label: 'Court of Appeals', href: 'https://ca.judiciary.gov.ph/' },
    { label: 'Sandiganbayan', href: 'https://sb.judiciary.gov.ph/' },
];

const aboutMenuItems = [
    { label: 'Overview', href: withBasePath('/about/overview') },
    { label: 'Organizational Structure', href: withBasePath('/about/organizational-structure') },
    { label: 'DepEd Data Privacy', href: withBasePath('/about/data-privacy') },
    { label: "Citizen's Charter", href: withBasePath('/about/citizens-charter') },
    { label: 'Freedom of Information', href: 'https://www.foi.gov.ph/' },
];

const headerBackgroundStyle = computed(() => ({
    backgroundImage: `url('${asset('/images/header-1.jpg')}')`,
    backgroundPosition: 'left center',
    backgroundSize: 'auto 100%',
}));

const resetCopiedState = () => {
    if (copyResetHandle.value) {
        clearTimeout(copyResetHandle.value);
        copyResetHandle.value = null;
    }
};

const resetHeaderDropdownCloseHandle = () => {
    if (headerDropdownCloseHandle.value) {
        clearTimeout(headerDropdownCloseHandle.value);
        headerDropdownCloseHandle.value = null;
    }
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

const closeMobileNavigation = () => {
    showingNavigationDropdown.value = false;
    activeMobileSection.value = null;
};

const toggleMobileNavigation = () => {
    if (showingNavigationDropdown.value) {
        closeMobileNavigation();
        return;
    }

    showingNavigationDropdown.value = true;
};

const isMobileSectionOpen = (section) => activeMobileSection.value === section;

const toggleMobileSection = (section) => {
    activeMobileSection.value = activeMobileSection.value === section ? null : section;
};

const isHeaderDropdownOpen = (menu) => activeHeaderDropdown.value === menu;

const openHeaderDropdown = (menu) => {
    resetHeaderDropdownCloseHandle();
    activeHeaderDropdown.value = menu;
};

const scheduleCloseHeaderDropdown = () => {
    resetHeaderDropdownCloseHandle();
    headerDropdownCloseHandle.value = setTimeout(() => {
        activeHeaderDropdown.value = null;
        headerDropdownCloseHandle.value = null;
    }, 120);
};

const navigateToLogin = () => {
    closeMobileNavigation();
    window.location.assign(loginPath);
};

onMounted(() => {
    updatePhilippineStandardTime();
    philippineStandardTimeHandle = window.setInterval(updatePhilippineStandardTime, 1000);
});

onBeforeUnmount(() => {
    resetCopiedState();
    resetHeaderDropdownCloseHandle();

    if (philippineStandardTimeHandle) {
        clearInterval(philippineStandardTimeHandle);
        philippineStandardTimeHandle = null;
    }
});
</script>

<template>
    <div class="user-portal-shell flex min-h-screen flex-col overflow-x-hidden bg-[#f5f6f8] pt-12 md:pt-11">
        <div class="fixed inset-x-0 top-0 z-[100] border-b border-[#cf7115] bg-[#f28c28] text-white shadow-[0_10px_24px_rgba(15,23,42,0.18)]">
            <div class="mx-auto flex w-full max-w-[1440px] items-center justify-between gap-3 px-4 py-2 text-[10px] font-bold uppercase tracking-[0.14em] sm:px-6 sm:text-[11px] sm:tracking-[0.18em] md:hidden">
                <a
                    href="https://www.gov.ph/"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="transition hover:text-white/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#f28c28]"
                >
                    GovPH
                </a>

                <button
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center text-white transition hover:text-white/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#f28c28]"
                    aria-controls="mobile-primary-navigation"
                    :aria-expanded="showingNavigationDropdown ? 'true' : 'false'"
                    aria-label="Open menu"
                    @click="toggleMobileNavigation"
                >
                    <span class="sr-only">Menu</span>
                    <svg
                        viewBox="0 0 20 20"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        aria-hidden="true"
                    >
                        <path
                            d="M3.5 6.5h13M3.5 10h13M3.5 13.5h13"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                        />
                    </svg>
                </button>
            </div>

            <div class="mx-auto hidden w-full max-w-[1440px] flex-col gap-2 px-4 py-2 text-[10px] font-bold uppercase tracking-[0.14em] sm:px-6 sm:text-[11px] sm:tracking-[0.18em] md:flex md:flex-row md:items-center md:justify-between lg:px-8">
                <div class="flex min-w-0 w-full flex-col gap-2 md:w-auto md:flex-row md:items-center md:gap-6">
                    <a
                        href="https://www.gov.ph/"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="transition hover:text-white/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#f28c28]"
                    >
                        GovPH
                    </a>
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                        <Link
                            :href="withBasePath('/resources')"
                            class="transition hover:text-white/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#f28c28]"
                        >
                            Home
                        </Link>

                        <div
                            class="relative flex items-center"
                            @mouseenter="openHeaderDropdown('about')"
                            @mouseleave="scheduleCloseHeaderDropdown"
                            @focusin="openHeaderDropdown('about')"
                            @focusout="scheduleCloseHeaderDropdown"
                        >
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.18em] transition hover:text-white/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#f28c28]"
                                :aria-expanded="isHeaderDropdownOpen('about') ? 'true' : 'false'"
                                aria-haspopup="true"
                            >
                                <span>About</span>
                                <svg
                                    viewBox="0 0 20 20"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-3.5 w-3.5 transition-transform duration-200"
                                    :class="isHeaderDropdownOpen('about') ? 'translate-y-px rotate-180' : ''"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M5 7.5 10 12.5l5-5"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </button>

                            <div
                                class="absolute left-0 top-full z-[180] w-64 pt-3 transition-all duration-150 ease-out"
                                :class="isHeaderDropdownOpen('about') ? 'pointer-events-auto translate-y-0 opacity-100' : 'pointer-events-none translate-y-1 opacity-0'"
                            >
                                <div
                                    class="overflow-hidden rounded-lg border border-slate-200 bg-[#fdfefe] text-slate-700 shadow-[0_18px_38px_rgba(15,23,42,0.18)]"
                                    role="menu"
                                    aria-label="About"
                                >
                                    <template v-for="item in aboutMenuItems" :key="item.label">
                                        <a
                                            v-if="item.href && item.href.startsWith('http')"
                                            :href="item.href"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="flex w-full items-center px-4 py-3 text-left text-sm font-semibold normal-case tracking-normal text-slate-700 transition hover:bg-slate-200 hover:text-slate-900 focus:outline-none focus-visible:bg-slate-200 focus-visible:text-slate-900"
                                            role="menuitem"
                                        >
                                            {{ item.label }}
                                        </a>
                                        <Link
                                            v-else-if="item.href"
                                            :href="item.href"
                                            class="flex w-full items-center px-4 py-3 text-left text-sm font-semibold normal-case tracking-normal text-slate-700 transition hover:bg-slate-200 hover:text-slate-900 focus:outline-none focus-visible:bg-slate-200 focus-visible:text-slate-900"
                                            role="menuitem"
                                        >
                                            {{ item.label }}
                                        </Link>
                                        <button
                                            v-else
                                            type="button"
                                            class="flex w-full items-center px-4 py-3 text-left text-sm font-semibold normal-case tracking-normal text-slate-700 transition hover:bg-slate-200 hover:text-slate-900 focus:outline-none focus-visible:bg-slate-200 focus-visible:text-slate-900"
                                            role="menuitem"
                                        >
                                            {{ item.label }}
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <a
                            :href="withBasePath('/resources#resource-categories')"
                            class="transition hover:text-white/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#f28c28]"
                        >
                            Resources
                        </a>
                    </div>
                </div>

                <div class="relative z-[140] flex w-full flex-wrap items-center gap-2 md:w-auto md:justify-end">
                    <Link
                        v-if="isAuthenticated"
                        :href="withBasePath('/materials')"
                        class="inline-flex w-full items-center justify-center rounded-full border border-white/45 bg-white/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.16em] text-white transition hover:bg-white hover:text-[#183f95] focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#f28c28] min-[360px]:w-auto sm:tracking-[0.18em]"
                    >
                        Materials Inventory
                    </Link>
                    <Link
                        v-if="isAuthenticated"
                        :href="withBasePath('/profile')"
                        class="inline-flex w-full items-center justify-center rounded-full border border-white/45 bg-white/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.16em] text-white transition hover:bg-white hover:text-[#183f95] focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#f28c28] min-[360px]:w-auto sm:tracking-[0.18em]"
                    >
                        Teacher Profile
                    </Link>
                    <Link
                        v-if="isAuthenticated"
                        :href="withBasePath('/logout')"
                        method="post"
                        as="button"
                        class="inline-flex w-full items-center justify-center rounded-full border border-white/45 bg-white/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.16em] text-white transition hover:bg-white hover:text-[#183f95] focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#f28c28] min-[360px]:w-auto sm:tracking-[0.18em]"
                    >
                        Log Out
                    </Link>
                    <button
                        v-else
                        type="button"
                        class="relative z-[160] inline-flex w-full pointer-events-auto items-center justify-center rounded-full border border-white/45 bg-white/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.16em] text-white transition hover:bg-white hover:text-[#183f95] focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#f28c28] min-[360px]:w-auto sm:tracking-[0.18em]"
                        @click="navigateToLogin"
                    >
                        Login
                    </button>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div
                v-if="showingNavigationDropdown"
                class="fixed inset-0 z-[190] md:hidden"
                role="dialog"
                aria-modal="true"
                aria-label="Mobile navigation"
            >
                <button
                    type="button"
                    data-mobile-menu-backdrop
                    class="absolute inset-0 bg-slate-950/40"
                    aria-label="Close menu"
                    @click="closeMobileNavigation"
                />

                <Transition
                    enter-active-class="transform transition duration-200 ease-out"
                    enter-from-class="-translate-x-full"
                    enter-to-class="translate-x-0"
                    leave-active-class="transform transition duration-150 ease-in"
                    leave-from-class="translate-x-0"
                    leave-to-class="-translate-x-full"
                >
                    <aside
                        v-if="showingNavigationDropdown"
                        id="mobile-primary-navigation"
                        class="absolute inset-y-0 left-0 flex w-[78vw] max-w-[300px] flex-col overflow-hidden bg-[#f28c28] text-white shadow-[0_18px_40px_rgba(15,23,42,0.32)]"
                    >
                        <div class="flex items-center justify-between border-b border-white/15 px-4 py-3.5">
                            <a
                                href="https://www.gov.ph/"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-[10px] font-black uppercase tracking-[0.16em] text-white transition hover:text-white/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#f28c28]"
                            >
                                GovPH
                            </a>

                            <button
                                type="button"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/25 bg-white/10 text-white transition hover:bg-white/16 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#f28c28]"
                                aria-label="Close menu"
                                @click="closeMobileNavigation"
                            >
                                <svg
                                    viewBox="0 0 20 20"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M5 5l10 10M15 5 5 15"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        stroke-linecap="round"
                                    />
                                </svg>
                            </button>
                        </div>

                        <div class="flex-1 overflow-y-auto px-4 py-3">
                            <nav class="space-y-1" aria-label="Mobile navigation links">
                                <Link
                                    :href="withBasePath('/resources')"
                                    class="flex w-full items-center justify-between px-1 py-3 text-left text-[13px] font-bold uppercase tracking-[0.16em] text-white/95 transition hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
                                    @click="closeMobileNavigation"
                                >
                                    Home
                                </Link>

                                <section class="border-t border-white/15 pt-1.5">
                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between gap-3 px-1 py-3 text-left text-[13px] font-bold uppercase tracking-[0.16em] text-white/95 transition hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
                                        :aria-expanded="isMobileSectionOpen('about') ? 'true' : 'false'"
                                        @click="toggleMobileSection('about')"
                                    >
                                        <span>About</span>
                                        <svg
                                            viewBox="0 0 20 20"
                                            fill="none"
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4 shrink-0 transition-transform duration-200"
                                            :class="isMobileSectionOpen('about') ? 'rotate-180' : ''"
                                            aria-hidden="true"
                                        >
                                            <path
                                                d="M5 7.5 10 12.5l5-5"
                                                stroke="currentColor"
                                                stroke-width="1.7"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                    </button>
                                    <div v-if="isMobileSectionOpen('about')" class="space-y-1 pb-2 pl-4">
                                        <template v-for="item in aboutMenuItems" :key="`mobile-about-${item.label}`">
                                            <a
                                                v-if="item.href && item.href.startsWith('http')"
                                                :href="item.href"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="flex w-full items-center px-1 py-2 text-left text-sm font-medium normal-case tracking-[0.01em] text-white/84 transition hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
                                                @click="closeMobileNavigation"
                                            >
                                                {{ item.label }}
                                            </a>
                                            <Link
                                                v-else-if="item.href"
                                                :href="item.href"
                                                class="flex w-full items-center px-1 py-2 text-left text-sm font-medium normal-case tracking-[0.01em] text-white/84 transition hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
                                                @click="closeMobileNavigation"
                                            >
                                                {{ item.label }}
                                            </Link>
                                            <div
                                                v-else
                                                class="flex w-full items-center px-1 py-2 text-left text-sm font-medium normal-case tracking-[0.01em] text-white/72"
                                            >
                                                {{ item.label }}
                                            </div>
                                        </template>
                                    </div>
                                </section>

                                <a
                                    :href="withBasePath('/resources#resource-categories')"
                                    class="flex w-full items-center justify-between border-t border-white/15 px-1 py-4 text-left text-[13px] font-bold uppercase tracking-[0.16em] text-white/95 transition hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
                                    @click="closeMobileNavigation"
                                >
                                    Resources
                                </a>

                                <div class="border-t border-white/15 pt-1.5">
                                    <Link
                                        v-if="isAuthenticated"
                                        :href="withBasePath('/materials')"
                                        class="flex w-full items-center justify-between px-1 py-3 text-left text-[13px] font-bold uppercase tracking-[0.16em] text-white/95 transition hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
                                        @click="closeMobileNavigation"
                                    >
                                        Materials Inventory
                                    </Link>

                                    <Link
                                        v-if="isAuthenticated"
                                        :href="withBasePath('/profile')"
                                        class="flex w-full items-center justify-between px-1 py-3 text-left text-[13px] font-bold uppercase tracking-[0.16em] text-white/95 transition hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
                                        @click="closeMobileNavigation"
                                    >
                                        Teacher Profile
                                    </Link>

                                    <Link
                                        v-if="isAuthenticated"
                                        :href="withBasePath('/logout')"
                                        method="post"
                                        as="button"
                                        class="flex w-full items-center justify-between px-1 py-3 text-left text-[13px] font-bold uppercase tracking-[0.16em] text-white/95 transition hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
                                        @click="closeMobileNavigation"
                                    >
                                        Log Out
                                    </Link>

                                    <button
                                        v-else
                                        type="button"
                                        class="flex w-full items-center justify-between px-1 py-3 text-left text-[13px] font-bold uppercase tracking-[0.16em] text-white/95 transition hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
                                        @click="navigateToLogin"
                                    >
                                        Login
                                    </button>
                                </div>
                            </nav>

                        </div>
                    </aside>
                </Transition>
            </div>
        </Teleport>

        <header class="user-portal-header relative z-[60] border-b border-slate-200 bg-white shadow-[0_14px_36px_rgba(15,23,42,0.08)]">

            <div
                class="relative z-[125] overflow-hidden bg-[#234eb7] bg-repeat-x text-white"
                :style="headerBackgroundStyle"
            >
                <div class="mx-auto flex w-full max-w-[1440px] flex-col gap-4 px-4 py-4 sm:px-6 lg:px-8 lg:py-5">
                    <div class="flex flex-col gap-3 md:gap-4 lg:flex-row lg:items-start lg:justify-between lg:gap-4">
                        <Link :href="withBasePath('/resources')" class="flex min-w-0 items-start gap-3 sm:gap-4 lg:max-w-[42rem]">
                            <img
                                :src="asset('/images/crystal-login-logo.png')"
                                alt="CRYSTAL Portal official logo"
                                class="mt-2 h-auto w-14 shrink-0 object-contain sm:w-16"
                            />
                            <div class="min-w-0 pt-1">
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-white/75 sm:text-[11px]">
                                    Republic of the Philippines
                                </p>
                                <h1 class="whitespace-nowrap text-[clamp(0.86rem,4vw,1.5rem)] font-black leading-tight tracking-tight">
                                    DepEd Ozamiz - CRYSTAL Portal
                                </h1>
                                <p class="mt-1 max-w-3xl text-[clamp(0.68rem,2.65vw,0.875rem)] font-medium leading-5 text-white/82 sm:whitespace-nowrap">
                                    Complete Resources for Year-Round Systematized Teaching and Learning
                                </p>
                            </div>
                        </Link>

                        <div class="w-full max-w-full text-left sm:self-end sm:text-right lg:self-start lg:mt-1">
                            <p class="text-[11px] font-medium leading-4 text-white sm:text-xs">Philippine Standard Time:</p>
                            <p class="break-words text-[11px] font-normal leading-4 text-white/90 sm:text-xs">
                                {{ philippineStandardTime }}
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            <div class="border-t border-slate-200/75 bg-[linear-gradient(180deg,#f9fbff_0%,#f2f6fc_100%)]">
                <div class="mx-auto flex w-full max-w-[1440px] px-4 py-1.5 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 flex-wrap items-center gap-3">
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
                            class="group flex min-w-0 max-w-full items-center gap-2 rounded-full text-sm font-semibold text-slate-700 transition hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
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

        <footer class="mt-8 border-t border-slate-200/80 bg-[linear-gradient(180deg,#e8eaee_0%,#dde1e7_100%)] text-slate-700">
            <div class="mx-auto w-full max-w-[1440px] px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-[1160px] py-6 pb-20 sm:py-5 sm:pb-5 lg:py-5">
                    <div class="grid gap-6 md:grid-cols-[minmax(0,1.16fr)_minmax(0,0.92fr)_minmax(0,0.92fr)] md:gap-5 lg:gap-6">
                        <section class="grid grid-cols-[4.75rem_minmax(0,1fr)] items-start gap-3 min-[360px]:grid-cols-[5.5rem_minmax(0,1fr)] sm:grid-cols-[8rem_minmax(0,1fr)] lg:grid-cols-[9.5rem_minmax(0,1fr)] lg:gap-3">
                            <img
                                :src="asset('/images/footlogo-removebg-preview.png')"
                                alt=""
                                class="mt-0.5 w-20 shrink-0 justify-self-start opacity-80 min-[360px]:w-24 sm:w-36 sm:opacity-100 lg:w-44"
                                aria-hidden="true"
                            />
                            <div class="min-w-0 pt-1 sm:pt-0">
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">Republic of the Philippines</p>
                                <p class="mt-2 text-[11px] leading-[1.35rem] text-slate-600 sm:mt-1.5 sm:leading-[1.25rem]">
                                    All content is in the public domain unless otherwise stated.
                                </p>
                            </div>
                        </section>

                        <section class="space-y-2.5">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">About GOVPH</h3>
                            <p class="text-[11px] leading-[1.35rem] text-slate-600 sm:leading-[1.25rem]">
                                Learn more about the Philippine government, its structure, how government works, and the people behind it.
                            </p>
                            <div class="flex flex-col gap-1.5 sm:gap-0.5">
                                <a
                                    v-for="link in govphLinks"
                                    :key="link.href"
                                    :href="link.href"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex w-fit items-center gap-1.5 text-[11px] font-semibold leading-[1.3rem] text-slate-700 transition hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-offset-2 sm:gap-1 sm:leading-[1.2rem]"
                                >
                                    <span>{{ link.label }}</span>
                                    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" aria-hidden="true">
                                        <path d="M7.5 12.5 12.5 7.5M8.333 7.5H12.5v4.167" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </a>
                            </div>
                        </section>

                        <section class="space-y-2.5">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">Government Links</h3>
                            <div class="grid gap-1.5 sm:gap-0.5">
                                <a
                                    v-for="link in governmentLinks"
                                    :key="link.href"
                                    :href="link.href"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="w-fit text-[11px] font-semibold leading-[1.3rem] text-slate-700 transition hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-offset-2 sm:leading-[1.2rem]"
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
