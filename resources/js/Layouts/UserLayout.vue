<script setup>
import AppFlashBanner from '@/Components/AppFlashBanner.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const isAuthenticated = computed(() => Boolean(user.value));
const flashSuccess = computed(() => page.props.flash?.success ?? '');
const flashError = computed(() => page.props.flash?.error ?? '');
const showingNavigationDropdown = ref(false);
const supportEmail = 'cid.ozamiz@depedozamiz.net';
const emailCopied = ref(false);
const copyResetHandle = ref(null);
const loginPath = '/login';
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

const resetCopiedState = () => {
    if (copyResetHandle.value) {
        clearTimeout(copyResetHandle.value);
        copyResetHandle.value = null;
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

const toggleMobileNavigation = () => {
    showingNavigationDropdown.value = !showingNavigationDropdown.value;
};

const navigateToLogin = () => {
    showingNavigationDropdown.value = false;
    window.location.assign(loginPath);
};

onMounted(() => {
    updatePhilippineStandardTime();
    philippineStandardTimeHandle = window.setInterval(updatePhilippineStandardTime, 1000);
});

onBeforeUnmount(() => {
    resetCopiedState();

    if (philippineStandardTimeHandle) {
        clearInterval(philippineStandardTimeHandle);
        philippineStandardTimeHandle = null;
    }
});
</script>

<template>
    <div class="user-portal-shell flex min-h-screen flex-col overflow-x-hidden bg-[#f5f6f8]">
        <header class="user-portal-header relative z-[120] border-b border-slate-200 bg-white shadow-[0_14px_36px_rgba(15,23,42,0.08)]">
            <div class="relative z-[130] bg-[#f28c28] text-white">
                <div class="mx-auto flex w-full max-w-[1440px] items-center justify-between gap-3 px-4 py-2 text-[11px] font-bold uppercase tracking-[0.18em] sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <a
                            href="https://www.gov.ph/"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="transition hover:text-white/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#f28c28]"
                        >
                            GovPH
                        </a>
                    </div>

                    <div class="relative z-[140] flex items-center gap-2">
                        <Link
                            v-if="isAuthenticated"
                            href="/materials"
                            class="rounded-full border border-white/45 bg-white/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.18em] text-white transition hover:bg-white hover:text-[#183f95] focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#f28c28]"
                        >
                            Materials Inventory
                        </Link>
                        <Link
                            v-if="isAuthenticated"
                            href="/profile"
                            class="rounded-full border border-white/45 bg-white/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.18em] text-white transition hover:bg-white hover:text-[#183f95] focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#f28c28]"
                        >
                            Teacher Profile
                        </Link>
                        <Link
                            v-if="isAuthenticated"
                            href="/logout"
                            method="post"
                            as="button"
                            class="rounded-full border border-white/45 bg-white/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.18em] text-white transition hover:bg-white hover:text-[#183f95] focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#f28c28]"
                        >
                            Log Out
                        </Link>
                        <button
                            v-else
                            type="button"
                            class="relative z-[160] inline-flex pointer-events-auto items-center rounded-full border border-white/45 bg-white/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.18em] text-white transition hover:bg-white hover:text-[#183f95] focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#f28c28]"
                            @click="navigateToLogin"
                        >
                            Login
                        </button>
                    </div>
                </div>
            </div>

            <div
                class="relative z-[125] overflow-hidden bg-[#234eb7] bg-repeat-x text-white"
                style="background-image: url('/images/header-1.jpg'); background-position: left center; background-size: auto 100%;"
            >
                <div class="mx-auto flex w-full max-w-[1440px] flex-col gap-4 px-4 py-4 sm:px-6 lg:px-8 lg:py-5">
                    <div class="flex justify-end lg:hidden">
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

                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between lg:gap-4">
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

                        <div class="self-end text-right lg:self-start lg:mt-1">
                            <p class="text-xs font-medium leading-4 text-white">Philippine Standard Time:</p>
                            <p class="text-xs font-normal leading-4 text-white/90">
                                {{ philippineStandardTime }}
                            </p>
                        </div>
                    </div>

                    <div v-if="showingNavigationDropdown" id="mobile-primary-navigation" class="space-y-3 lg:hidden">
                        <Link
                            v-if="isAuthenticated"
                            href="/materials"
                            class="inline-flex w-fit items-center rounded-full border border-white/35 bg-white/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.16em] text-white transition hover:bg-white hover:text-[#183f95] focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#214dc1]"
                        >
                            Materials Inventory
                        </Link>

                        <Link
                            v-if="isAuthenticated"
                            href="/profile"
                            class="inline-flex w-fit items-center rounded-full border border-white/35 bg-white/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.16em] text-white transition hover:bg-white hover:text-[#183f95] focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#214dc1]"
                        >
                            Teacher Profile
                        </Link>
                        <button
                            v-else
                            type="button"
                            class="relative z-[160] inline-flex w-fit pointer-events-auto items-center rounded-full border border-white/35 bg-white/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.16em] text-white transition hover:bg-white hover:text-[#183f95] focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#214dc1]"
                            @click="navigateToLogin"
                        >
                            Login
                        </button>

                        <div
                            v-if="user"
                            class="rounded-[1.25rem] border border-white/18 bg-white/8 px-4 py-4 backdrop-blur"
                        >
                            <p class="truncate text-sm font-black text-white">{{ user.name }}</p>
                            <p class="truncate text-[11px] font-semibold uppercase tracking-[0.14em] text-white/68">{{ user.email }}</p>
                        </div>

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
