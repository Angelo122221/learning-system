<script setup>
import AppEmptyState from '@/Components/AppEmptyState.vue';
import DepedSystemsStrip from '@/Components/DepedSystemsStrip.vue';
import Modal from '@/Components/Modal.vue';
import PortalLinkSection from '@/Components/PortalLinkSection.vue';
import { depedSystemsLinks, officialLinks } from '@/data/portalLinks';
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    announcements: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const isAuthenticated = computed(() => Boolean(page.props.auth?.user));
const isAnnouncementsModalOpen = ref(false);
const localAnnouncements = ref([]);
const pendingReadIds = ref([]);
const pendingDismissIds = ref([]);
const isMarkingAllAnnouncementsRead = ref(false);
const expandedAnnouncementIds = ref([]);

const noticeParagraphs = [
    "In accordance with the Department of Education's (DepEd) mandate to protect and promote the right to and access to quality basic education, DepEd collects various data and information, including personal information, from various subjects using different systems.",
    'In the processing of these data and information, DepEd is committed to ensure the free flow of information as required under the Freedom of Information Act (Executive Order No. 2, s. 2016) and to protect and respect the confidentiality and privacy of these data and information as required under the Data Privacy Act No. 10173.',
    'Request for data and information, unless access is denied when such data and information fall under any of the exceptions enshrined in the Constitution, existing law or jurisprudence, shall be guided by the DepEd Freedom of Information Manual (Department Order No. 72, s. 2016).',
    'Only authorized DepEd personnel have access to personal information collected, the exchange of which will be facilitated through email and web applications. These will be stored in a database in accordance with government policies, rules, regulations, and guidelines.',
];

const analyticsColumns = [
    [
        'Your IP address',
        'The search terms you used',
        'The pages and internal links accessed on our site',
        'The date and time you visited the site',
    ],
    [
        'Geographic location',
        'The referring site or platform through which you clicked through to this site (if any)',
        'Your operating system',
        'Web browser type, among others.',
    ],
];

const mediaUrl = (path) => `/media/${path}`;

const announcementDateFormatter = new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
});

const normalizeAnnouncement = (announcement) => ({
    ...announcement,
    is_read: Boolean(announcement?.is_read),
});

const isAnnouncementExpanded = (announcementId) => expandedAnnouncementIds.value.includes(announcementId);
const isReadRequestPending = (announcementId) => pendingReadIds.value.includes(announcementId);
const isDismissRequestPending = (announcementId) => pendingDismissIds.value.includes(announcementId);

const visibleAnnouncements = computed(() => [...localAnnouncements.value].sort((left, right) => {
    const leftTimestamp = left.created_at ? Date.parse(left.created_at) : 0;
    const rightTimestamp = right.created_at ? Date.parse(right.created_at) : 0;

    return rightTimestamp - leftTimestamp;
}));

const unreadAnnouncementCount = computed(() => visibleAnnouncements.value.filter((announcement) => !announcement.is_read).length);
const announcementCount = computed(() => visibleAnnouncements.value.length);
const hasAnnouncements = computed(() => announcementCount.value > 0);

const formatAnnouncementTimestamp = (timestamp) => {
    if (!timestamp) {
        return 'Date unavailable';
    }

    const parsedDate = new Date(timestamp);

    if (Number.isNaN(parsedDate.getTime())) {
        return 'Date unavailable';
    }

    return announcementDateFormatter.format(parsedDate);
};

const syncAnnouncementsFromProps = (incomingAnnouncements) => {
    localAnnouncements.value = (incomingAnnouncements ?? []).map(normalizeAnnouncement);

    const visibleIds = localAnnouncements.value.map((announcement) => announcement.id);
    expandedAnnouncementIds.value = expandedAnnouncementIds.value.filter((id) => visibleIds.includes(id));
    pendingReadIds.value = pendingReadIds.value.filter((id) => visibleIds.includes(id));
    pendingDismissIds.value = pendingDismissIds.value.filter((id) => visibleIds.includes(id));
};

const markAnnouncementAsReadLocally = (announcementId) => {
    localAnnouncements.value = localAnnouncements.value.map((announcement) => (
        announcement.id === announcementId
            ? {
                ...announcement,
                is_read: true,
                read_at: announcement.read_at ?? new Date().toISOString(),
            }
            : announcement
    ));
};

const persistAnnouncementRead = async (announcementId) => {
    if (isReadRequestPending(announcementId)) {
        return;
    }

    pendingReadIds.value = [...pendingReadIds.value, announcementId];

    try {
        await axios.post(`/resources/announcements/${announcementId}/read`);
    } finally {
        pendingReadIds.value = pendingReadIds.value.filter((id) => id !== announcementId);
    }
};

const toggleAnnouncementExpansion = (announcement) => {
    const isExpanded = isAnnouncementExpanded(announcement.id);

    if (isExpanded) {
        expandedAnnouncementIds.value = expandedAnnouncementIds.value.filter((id) => id !== announcement.id);
        return;
    }

    expandedAnnouncementIds.value = [...expandedAnnouncementIds.value, announcement.id];

    if (!announcement.is_read) {
        markAnnouncementAsReadLocally(announcement.id);
        void persistAnnouncementRead(announcement.id);
    }
};

const markAllAnnouncementsAsRead = async () => {
    if (unreadAnnouncementCount.value === 0 || isMarkingAllAnnouncementsRead.value) {
        return;
    }

    const unreadIds = visibleAnnouncements.value
        .filter((announcement) => !announcement.is_read)
        .map((announcement) => announcement.id);

    if (!unreadIds.length) {
        return;
    }

    unreadIds.forEach((announcementId) => {
        markAnnouncementAsReadLocally(announcementId);
    });

    isMarkingAllAnnouncementsRead.value = true;

    try {
        await axios.post('/resources/announcements/read-all');
    } finally {
        isMarkingAllAnnouncementsRead.value = false;
        pendingReadIds.value = pendingReadIds.value.filter((id) => !unreadIds.includes(id));
    }
};

const dismissAnnouncement = async (announcementId) => {
    if (isDismissRequestPending(announcementId)) {
        return;
    }

    pendingDismissIds.value = [...pendingDismissIds.value, announcementId];
    expandedAnnouncementIds.value = expandedAnnouncementIds.value.filter((id) => id !== announcementId);
    localAnnouncements.value = localAnnouncements.value.filter((announcement) => announcement.id !== announcementId);

    try {
        await axios.post(`/resources/announcements/${announcementId}/dismiss`);
    } finally {
        pendingDismissIds.value = pendingDismissIds.value.filter((id) => id !== announcementId);
    }
};

const getAnnouncementToggleLabel = (announcement) => (
    isAnnouncementExpanded(announcement.id)
        ? 'Collapse announcement'
        : announcement.is_read
            ? 'Expand announcement'
            : 'Expand and mark announcement as read'
);

const isAnnouncementDimmed = (announcement) => announcement.is_read && !isAnnouncementExpanded(announcement.id);
const getAnnouncementContentStateClass = (announcementId) => (
    isAnnouncementExpanded(announcementId)
        ? 'announcement-content-expanded'
        : 'announcement-content-collapsed'
);

watch(
    () => props.announcements,
    (incomingAnnouncements) => {
        syncAnnouncementsFromProps(incomingAnnouncements);
    },
    { immediate: true, deep: true },
);
</script>

<template>
    <Head title="DepEd Data Privacy" />

    <UserLayout>
        <section class="w-full">
            <div class="pt-0.5 pb-0.5">
                    <h1
                        class="font-extrabold tracking-tight text-slate-950"
                        style="font-size: clamp(1.45rem, 2.7vw, 2.45rem); line-height: 0.9;"
                    >
                        Data Privacy Notice
                    </h1>
            </div>

            <div class="mt-6 grid items-stretch gap-4 md:grid-cols-[minmax(0,1.22fr)_minmax(0,1fr)] lg:gap-5">
                <section class="h-full overflow-hidden rounded-[1.35rem] border border-slate-200 bg-white shadow-[0_12px_28px_rgba(15,23,42,0.06)]">
                    <div class="px-5 py-6 sm:px-6 sm:py-7 lg:px-7">
                        <div class="space-y-5 text-[15px] leading-8 text-slate-700 sm:text-[16px]">
                            <p v-for="paragraph in noticeParagraphs" :key="paragraph">
                                {{ paragraph }}
                            </p>

                            <p>
                                You have the right to ask for a copy of any personal information DepEd holds about you, as well as the right to ask for its correction, if found erroneous, or deletion on reasonable grounds. You may contact
                                <a
                                    href="mailto:dataprivacy.dpo@deped.gov.ph"
                                    class="font-semibold text-blue-700 underline decoration-blue-200 underline-offset-4 transition hover:text-blue-800 hover:decoration-blue-400"
                                >
                                    dataprivacy.dpo@deped.gov.ph
                                </a>
                            </p>
                        </div>
                    </div>
                </section>

                <section class="h-full overflow-hidden rounded-[1.35rem] border border-slate-200 bg-white shadow-[0_12px_28px_rgba(15,23,42,0.06)]">
                    <div class="flex h-full flex-col px-5 py-6 sm:px-6 sm:py-7 lg:px-7">
                        <div class="min-w-0">
                            <h2 class="text-lg font-black tracking-tight text-orange-500">Website Analytics</h2>
                            <p class="mt-2 text-[15px] leading-7 text-slate-700 sm:text-[16px]">
                                The DepEd uses Google analytics, a third-party service to analyze the web traffic data for us. This service uses cookies. Data generated are not shared with any other party. Only non-identifiable web traffic data are analyzed, including:
                            </p>
                        </div>

                        <div class="mt-5">
                            <ul class="list-disc space-y-2 pl-6 text-[15px] leading-7 text-slate-700 marker:text-orange-400 sm:text-[16px]">
                                <li v-for="item in analyticsColumns.flat()" :key="item">
                                    {{ item }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>
            </div>
        </section>

        <section class="mt-8 -mb-4 md:-mb-6" aria-labelledby="portal-links-heading">
            <h2 id="portal-links-heading" class="sr-only">DepEd systems and official links</h2>

            <PortalLinkSection
                title="DepEd Systems and Official websites"
                eyebrow=""
                :items="officialLinks"
                layout="logo-top"
                :show-section-icon="false"
                :hide-title="true"
                :show-header-divider="false"
                :show-titles-on-hover-only="true"
                :flat="true"
            >
                <template #prepend>
                    <DepedSystemsStrip :items="depedSystemsLinks" :show-heading="false" />
                </template>
            </PortalLinkSection>
        </section>

        <template v-if="isAuthenticated">
            <button
                type="button"
                class="fixed bottom-5 right-5 z-40 inline-flex h-14 w-14 items-center justify-center rounded-full border border-blue-200 bg-white text-blue-600 shadow-[0_12px_26px_rgba(15,23,42,0.10)] transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-300 hover:text-blue-700 hover:shadow-[0_16px_30px_rgba(37,99,235,0.14)] focus:outline-none focus-visible:ring-4 focus-visible:ring-blue-100 active:translate-y-0 active:scale-[0.96] sm:bottom-6 sm:right-6 sm:h-[3.75rem] sm:w-[3.75rem]"
                :aria-label="hasAnnouncements
                    ? unreadAnnouncementCount
                        ? `Open announcements. ${unreadAnnouncementCount} unread.`
                        : 'Open announcements. All caught up.'
                    : 'Open announcements. No announcements yet.'"
                @click="isAnnouncementsModalOpen = true"
            >
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-7 w-7 sm:h-8 sm:w-8"
                    aria-hidden="true"
                >
                    <path
                        d="M12 4.75a4.25 4.25 0 0 0-4.25 4.25v2.254c0 .72-.214 1.425-.615 2.023L5.75 15.25h12.5l-1.385-1.973a3.5 3.5 0 0 1-.615-2.023V9A4.25 4.25 0 0 0 12 4.75Z"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M9.5 18a2.5 2.5 0 0 0 5 0"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>

                <span
                    v-if="unreadAnnouncementCount > 0"
                    class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full border-2 border-white bg-orange-500 px-1 text-[10px] font-black leading-none text-white shadow-[0_8px_18px_rgba(242,140,40,0.24)]"
                    aria-label="Unread announcement count"
                >
                    {{ unreadAnnouncementCount > 9 ? '9+' : unreadAnnouncementCount }}
                </span>
            </button>

            <Modal :show="isAnnouncementsModalOpen" max-width="xl" position="center" @close="isAnnouncementsModalOpen = false">
                <div class="bg-[linear-gradient(180deg,#ffffff_0%,#f8fbff_100%)]">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-5 sm:px-6">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.22em] text-blue-500">Announcements</p>
                            <h2 class="mt-2 text-xl font-black tracking-tight text-slate-950 sm:text-2xl">Latest updates</h2>
                            <p class="mt-2 text-sm font-medium leading-6 text-slate-500">
                                Stay updated with the latest announcements
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <button
                                v-if="unreadAnnouncementCount > 0"
                                type="button"
                                class="inline-flex items-center justify-center rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-[11px] font-black uppercase tracking-[0.18em] text-blue-700 transition hover:border-blue-300 hover:bg-blue-100 focus:outline-none focus-visible:ring-4 focus-visible:ring-blue-100 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="isMarkingAllAnnouncementsRead"
                                @click="markAllAnnouncementsAsRead"
                            >
                                {{ isMarkingAllAnnouncementsRead ? 'Updating...' : 'Mark all as read' }}
                            </button>

                            <button
                                type="button"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:text-slate-800 focus:outline-none focus-visible:ring-4 focus-visible:ring-blue-100 active:scale-[0.97]"
                                aria-label="Close announcements"
                                @click="isAnnouncementsModalOpen = false"
                            >
                                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" aria-hidden="true">
                                    <path d="M5 5l10 10M15 5 5 15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="max-h-[min(68vh,38rem)] overflow-y-auto px-5 py-5 sm:px-6 sm:py-6">
                        <AppEmptyState
                            v-if="!hasAnnouncements"
                            title="No announcements yet"
                            message="Check back later for important updates"
                        />

                        <div v-else class="space-y-4">
                            <article
                                v-for="announcement in visibleAnnouncements"
                                :key="announcement.id"
                                class="overflow-hidden rounded-[1.6rem] border shadow-[0_14px_30px_rgba(15,23,42,0.06)] transition-colors"
                                :class="isAnnouncementDimmed(announcement) ? 'border-slate-200 bg-slate-50/95' : 'border-blue-200 bg-white'"
                            >
                                <img
                                    v-if="announcement.image_path"
                                    :src="mediaUrl(announcement.image_path)"
                                    :alt="announcement.title"
                                    class="h-44 w-full object-cover sm:h-52"
                                />
                                <div class="p-4 sm:p-5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    v-if="!announcement.is_read"
                                                    class="h-2.5 w-2.5 shrink-0 rounded-full bg-red-500"
                                                    aria-label="Unread announcement"
                                                />
                                                <h3
                                                    class="text-base font-black sm:text-lg"
                                                    :class="isAnnouncementDimmed(announcement) ? 'text-slate-600' : 'text-slate-950'"
                                                >
                                                    {{ announcement.title }}
                                                </h3>
                                            </div>
                                            <p class="mt-2 text-xs font-semibold uppercase tracking-[0.16em]" :class="isAnnouncementDimmed(announcement) ? 'text-slate-400' : 'text-slate-500'">
                                                {{ formatAnnouncementTimestamp(announcement.created_at) }}
                                            </p>
                                        </div>

                                        <button
                                            type="button"
                                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border transition focus:outline-none focus-visible:ring-4 focus-visible:ring-blue-100"
                                            :class="isAnnouncementDimmed(announcement)
                                                ? 'border-slate-200 bg-slate-100 text-slate-400 hover:border-slate-300 hover:text-slate-600'
                                                : 'border-blue-200 bg-blue-50 text-blue-600 hover:border-blue-300 hover:text-blue-700'"
                                            :aria-label="getAnnouncementToggleLabel(announcement)"
                                            @click="toggleAnnouncementExpansion(announcement)"
                                        >
                                            <svg
                                                viewBox="0 0 20 20"
                                                fill="none"
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4 transition-transform duration-200"
                                                :class="isAnnouncementExpanded(announcement.id) ? 'rotate-180' : ''"
                                                aria-hidden="true"
                                            >
                                                <path
                                                    d="M5 7.5 10 12.5l5-5"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                />
                                            </svg>
                                        </button>
                                    </div>

                                    <div
                                        class="announcement-content-shell mt-3"
                                        :class="getAnnouncementContentStateClass(announcement.id)"
                                    >
                                        <p
                                            class="whitespace-pre-line text-sm font-medium leading-6 transition-colors duration-200"
                                            :class="isAnnouncementDimmed(announcement) ? 'text-slate-400' : 'text-slate-700'"
                                        >
                                            {{ announcement.content }}
                                        </p>
                                    </div>

                                    <div class="mt-4 flex justify-end">
                                        <button
                                            type="button"
                                            class="inline-flex w-fit items-center justify-center rounded-full border border-slate-200 px-4 py-2 text-[11px] font-black uppercase tracking-[0.18em] text-slate-600 transition hover:border-slate-300 hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus-visible:ring-4 focus-visible:ring-blue-100 disabled:cursor-not-allowed disabled:opacity-60"
                                            :disabled="isDismissRequestPending(announcement.id)"
                                            @click="dismissAnnouncement(announcement.id)"
                                        >
                                            {{ isDismissRequestPending(announcement.id) ? 'Dismissing...' : 'Dismiss' }}
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>
            </Modal>
        </template>
    </UserLayout>
</template>

<style scoped>
.announcement-content-shell {
    overflow: hidden;
    transition: max-height 260ms ease, opacity 220ms ease;
}

.announcement-content-collapsed {
    max-height: 4.5rem;
    opacity: 0.92;
}

.announcement-content-expanded {
    max-height: 40rem;
    opacity: 1;
}
</style>
