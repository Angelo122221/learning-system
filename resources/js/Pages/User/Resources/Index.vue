<script setup>
import AppEmptyState from '@/Components/AppEmptyState.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import DepedSystemsStrip from '@/Components/DepedSystemsStrip.vue';
import Modal from '@/Components/Modal.vue';
import PortalLinkSection from '@/Components/PortalLinkSection.vue';
import { depedSystemsLinks, officialLinks } from '@/data/portalLinks';
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import UserFolderItem from './FolderItem.vue';

const props = defineProps({
    folders: Array,
    announcements: Array,
    carouselImages: Array,
    featuredVideos: Array,
});

const page = usePage();
const isAuthenticated = computed(() => Boolean(page.props.auth?.user));
const isAnnouncementsModalOpen = ref(false);
const resourceCategoriesSection = ref(null);
const resourceCategoriesGrid = ref(null);
const isResourceCategoriesExpanded = ref(false);
const resourceCategoriesCollapsedHeight = ref(null);
const resourceCategoriesContentHeight = ref(null);
const canExpandResourceCategories = ref(false);
const localAnnouncements = ref([]);
const expandedAnnouncementIds = ref([]);
const pendingReadIds = ref([]);
const pendingDismissIds = ref([]);
const isMarkingAllAnnouncementsRead = ref(false);
const showcaseSlides = computed(() => props.carouselImages ?? []);
const mediaUrl = (path) => `/media/${path}`;
const mainVideo = computed(() => props.featuredVideos[0] ?? null);
const featuredVideoSection = ref(null);
const featuredVideoIframe = ref(null);
let featuredVideoObserver = null;
let resourceCategoriesResizeObserver = null;

const createYoutubeEmbedUrl = (videoId) => {
    if (!videoId) {
        return '';
    }

    const embedUrl = new URL(`https://www.youtube.com/embed/${videoId}`);
    embedUrl.searchParams.set('enablejsapi', '1');

    return embedUrl.toString();
};

const mainVideoEmbedUrl = computed(() => {
    const link = mainVideo.value?.youtube_link;

    if (!link) {
        return '';
    }

    try {
        const parsedUrl = new URL(link);

        if (parsedUrl.hostname.includes('youtu.be')) {
            const videoId = parsedUrl.pathname.split('/').filter(Boolean)[0];
            return createYoutubeEmbedUrl(videoId);
        }

        if (parsedUrl.pathname.startsWith('/embed/')) {
            const videoId = parsedUrl.pathname.split('/').filter(Boolean)[1];
            return createYoutubeEmbedUrl(videoId);
        }

        if (parsedUrl.pathname.startsWith('/shorts/')) {
            const videoId = parsedUrl.pathname.split('/').filter(Boolean)[1];
            return createYoutubeEmbedUrl(videoId);
        }

        const videoId = parsedUrl.searchParams.get('v');
        return createYoutubeEmbedUrl(videoId);
    } catch {
        return '';
    }
});
const activeShowcaseIndex = ref(0);
const isMobileShowcaseViewport = ref(false);
const showcaseDotWindowStart = ref(0);
const autoplayHandle = ref(null);
const pressedShowcaseKey = ref(null);
const isShowcaseHovered = ref(false);
const blueWaveBackLeftAnimation = ref(null);
const blueWaveBackRightAnimation = ref(null);
const blueWaveGlowLeftAnimation = ref(null);
const blueWaveGlowRightAnimation = ref(null);
const blueWaveLeftAnimation = ref(null);
const blueWaveRightAnimation = ref(null);
const orangeWaveBackLeftAnimation = ref(null);
const orangeWaveBackRightAnimation = ref(null);
const orangeWaveGlowLeftAnimation = ref(null);
const orangeWaveGlowRightAnimation = ref(null);
const orangeWaveLeftAnimation = ref(null);
const orangeWaveRightAnimation = ref(null);
let showcasePressHandle = null;
let showcaseWaveTrailHandle = null;
const secondaryBlueWavePath = 'M0 204C118 232 238 224 362 160C484 90 606 6 732 12C868 18 990 140 1112 198C1222 248 1332 252 1440 228L1440 250C1330 270 1216 266 1092 230C964 196 850 110 728 112C604 116 486 198 362 236C238 274 118 272 0 236Z';
const baseBlueWavePath = 'M0 192C116 220 236 210 360 138C482 62 604 -24 732 -18C872 -10 992 124 1114 188C1224 240 1334 247 1440 218L1440 256C1330 278 1214 274 1090 238C964 204 852 108 732 102C608 96 490 184 364 228C238 272 116 270 0 228Z';
const secondaryLeftBlueWavePath = 'M0 204C118 218 236 198 352 128C472 44 596 -34 722 -4C860 28 984 154 1110 204C1220 250 1332 254 1440 228L1440 250C1330 268 1218 262 1094 224C964 188 848 104 724 124C596 144 476 228 350 256C230 284 114 280 0 236Z';
const leftBlueWavePath = 'M0 192C116 204 234 184 350 108C470 20 596 -58 718 -30C854 -2 980 140 1110 194C1222 242 1334 249 1440 218L1440 256C1330 276 1216 272 1092 232C962 196 848 110 724 116C594 124 472 210 348 246C226 284 112 280 0 228Z';
const secondaryRightBlueWavePath = 'M0 204C118 236 240 230 366 172C490 108 614 34 746 0C886 -30 1010 118 1136 192C1242 244 1344 250 1440 228L1440 250C1330 274 1214 270 1088 236C958 202 842 118 722 100C602 82 488 166 368 220C244 272 120 274 0 236Z';
const rightBlueWavePath = 'M0 192C116 226 238 218 364 150C486 84 610 4 742 -20C882 -46 1008 104 1136 180C1244 236 1344 245 1440 218L1440 256C1330 280 1214 278 1088 244C958 210 842 122 720 104C598 86 482 168 360 220C236 270 116 270 0 228Z';
const secondaryOrangeWavePath = 'M0 236C118 276 240 278 364 238C490 198 608 116 728 112C850 110 964 196 1092 230C1216 266 1330 270 1440 250L1440 310C1328 336 1216 344 1090 338C962 312 850 256 728 266C604 278 490 340 364 360C240 378 120 364 0 302Z';
const baseOrangeWavePath = 'M0 228C116 270 238 272 364 228C490 184 608 96 732 102C852 108 964 204 1090 238C1214 274 1330 278 1440 256L1440 332C1328 366 1214 374 1088 366C962 338 852 276 732 284C608 292 492 358 364 382C240 404 118 386 0 290Z';
const secondaryLeftOrangeWavePath = 'M0 236C118 282 240 286 366 250C490 214 606 132 724 124C848 104 964 188 1094 224C1218 262 1330 268 1440 250L1440 304C1328 328 1218 336 1092 332C964 308 850 264 724 282C598 300 478 356 352 374C230 390 114 374 0 302Z';
const leftOrangeWavePath = 'M0 228C116 282 238 286 364 242C488 198 606 112 724 116C848 120 962 196 1092 232C1216 272 1330 276 1440 256L1440 320C1328 350 1218 358 1092 350C964 324 850 266 724 286C594 308 474 386 350 402C226 418 112 386 0 290Z';
const secondaryRightOrangeWavePath = 'M0 236C118 276 242 274 368 230C488 186 602 82 722 100C842 118 958 202 1088 236C1214 270 1330 274 1440 250L1440 322C1328 354 1216 364 1090 358C962 334 848 284 722 280C602 276 490 330 366 352C242 374 120 364 0 302Z';
const rightOrangeWavePath = 'M0 228C116 270 236 270 360 220C482 168 598 86 720 104C842 122 958 210 1088 244C1214 278 1330 280 1440 256L1440 344C1330 388 1218 398 1092 394C958 364 844 286 720 278C602 270 486 342 364 374C240 404 118 388 0 290Z';

const wrapSlideIndex = (index) => {
    const totalSlides = showcaseSlides.value.length;

    if (!totalSlides) {
        return 0;
    }

    return (index % totalSlides + totalSlides) % totalSlides;
};

const updateShowcaseViewport = () => {
    if (typeof window === 'undefined') {
        isMobileShowcaseViewport.value = false;
        return;
    }

    isMobileShowcaseViewport.value = window.innerWidth < 768;
};

const visibleCarouselOffsets = computed(() => {
    const totalSlides = showcaseSlides.value.length;

    if (totalSlides <= 1) return [0];
    if (totalSlides === 2) return [0, 1];
    if (isMobileShowcaseViewport.value) return [-1, 0, 1];
    if (totalSlides <= 4) return [-1, 0, 1];

    return [-2, -1, 0, 1, 2];
});

const visibleCarouselSlides = computed(() => visibleCarouselOffsets.value.map((offset) => ({
    offset,
    slide: showcaseSlides.value[wrapSlideIndex(activeShowcaseIndex.value + offset)],
    key: `${showcaseSlides.value[wrapSlideIndex(activeShowcaseIndex.value + offset)]?.id ?? 'slide'}-${offset}`,
})));
const getResourceCategoryRows = (cards) => {
    const rows = [];

    cards.forEach((card) => {
        const top = Math.round(card.offsetTop);
        const existingRow = rows.find((row) => Math.abs(row.top - top) <= 1);

        if (existingRow) {
            existingRow.height = Math.max(existingRow.height, card.offsetHeight);
            return;
        }

        rows.push({
            top,
            height: card.offsetHeight,
        });
    });

    return rows.sort((left, right) => left.top - right.top);
};

const measureResourceCategories = async () => {
    await nextTick();

    const grid = resourceCategoriesGrid.value;

    if (!grid) {
        resourceCategoriesCollapsedHeight.value = null;
        resourceCategoriesContentHeight.value = null;
        canExpandResourceCategories.value = false;
        return;
    }

    const cards = Array.from(grid.children).filter((child) => child instanceof HTMLElement);

    if (!cards.length) {
        resourceCategoriesCollapsedHeight.value = null;
        resourceCategoriesContentHeight.value = null;
        canExpandResourceCategories.value = false;
        return;
    }

    const rows = getResourceCategoryRows(cards);
    const contentHeight = Math.ceil(grid.scrollHeight);

    resourceCategoriesContentHeight.value = contentHeight;

    if (rows.length <= 3) {
        resourceCategoriesCollapsedHeight.value = contentHeight;
        canExpandResourceCategories.value = false;
        isResourceCategoriesExpanded.value = false;
        return;
    }

    const thirdRow = rows[2];
    const collapsedHeight = Math.min(
        contentHeight,
        Math.round(thirdRow.top + thirdRow.height),
    );

    resourceCategoriesCollapsedHeight.value = collapsedHeight;
    canExpandResourceCategories.value = contentHeight > collapsedHeight + 8;

    if (!canExpandResourceCategories.value) {
        isResourceCategoriesExpanded.value = false;
    }
};

const resourceCategoriesContainerStyle = computed(() => {
    if (!resourceCategoriesContentHeight.value) {
        return {};
    }

    return {
        maxHeight: `${isResourceCategoriesExpanded.value
            ? resourceCategoriesContentHeight.value
            : (resourceCategoriesCollapsedHeight.value ?? resourceCategoriesContentHeight.value)}px`,
    };
});

const toggleResourceCategories = async () => {
    if (!canExpandResourceCategories.value) {
        return;
    }

    if (isResourceCategoriesExpanded.value) {
        isResourceCategoriesExpanded.value = false;
        await nextTick();

        const section = resourceCategoriesSection.value;

        if (section && window.scrollY > section.offsetTop - 32) {
            section.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
            });
        }

        return;
    }

    await measureResourceCategories();
    await new Promise((resolve) => {
        if (typeof window === 'undefined' || typeof window.requestAnimationFrame !== 'function') {
            resolve();
            return;
        }

        window.requestAnimationFrame(() => resolve());
    });

    isResourceCategoriesExpanded.value = true;
};

const observeResourceCategoriesGrid = (grid, previousGrid = null) => {
    if (!resourceCategoriesResizeObserver) {
        return;
    }

    if (previousGrid) {
        resourceCategoriesResizeObserver.unobserve(previousGrid);
    }

    if (grid) {
        resourceCategoriesResizeObserver.observe(grid);
    }
};

const MAX_SHOWCASE_DOTS = 5;

const clampShowcaseDotWindowStart = (startIndex) => {
    const maxStart = Math.max(showcaseSlides.value.length - MAX_SHOWCASE_DOTS, 0);

    return Math.min(Math.max(startIndex, 0), maxStart);
};

const visibleShowcaseDots = computed(() => {
    const totalSlides = showcaseSlides.value.length;

    if (totalSlides <= MAX_SHOWCASE_DOTS) {
        return showcaseSlides.value.map((slide, index) => ({
            id: slide.id ?? `slide-${index}`,
            index,
        }));
    }

    const startIndex = clampShowcaseDotWindowStart(showcaseDotWindowStart.value);
    const endIndex = Math.min(startIndex + MAX_SHOWCASE_DOTS - 1, totalSlides - 1);

    return Array.from({ length: endIndex - startIndex + 1 }, (_, offset) => {
        const index = startIndex + offset;
        const slide = showcaseSlides.value[index];

        return {
            id: slide?.id ?? `slide-${index}`,
            index,
        };
    });
});

const syncShowcaseDotWindow = (nextIndex, previousIndex = null) => {
    const totalSlides = showcaseSlides.value.length;

    if (totalSlides <= MAX_SHOWCASE_DOTS) {
        showcaseDotWindowStart.value = 0;
        return;
    }

    const maxStart = totalSlides - MAX_SHOWCASE_DOTS;
    const currentStart = clampShowcaseDotWindowStart(showcaseDotWindowStart.value);
    const currentEnd = currentStart + MAX_SHOWCASE_DOTS - 1;

    if (previousIndex !== null) {
        if (previousIndex === totalSlides - 1 && nextIndex === 0) {
            showcaseDotWindowStart.value = 0;
            return;
        }

        if (previousIndex === 0 && nextIndex === totalSlides - 1) {
            showcaseDotWindowStart.value = maxStart;
            return;
        }
    }

    if (nextIndex < currentStart) {
        showcaseDotWindowStart.value = nextIndex;
        return;
    }

    if (nextIndex > currentEnd) {
        showcaseDotWindowStart.value = nextIndex - (MAX_SHOWCASE_DOTS - 1);
        return;
    }

    showcaseDotWindowStart.value = currentStart;
};

const setActiveShowcase = (index) => {
    activeShowcaseIndex.value = wrapSlideIndex(index);
};

const resumeShowcaseAutoplayIfAllowed = () => {
    if (!isShowcaseHovered.value) {
        startShowcaseAutoplay();
    }
};

const pulseShowcaseCard = (key) => {
    if (showcasePressHandle) {
        clearTimeout(showcasePressHandle);
    }

    pressedShowcaseKey.value = key;
    showcasePressHandle = setTimeout(() => {
        pressedShowcaseKey.value = null;
        showcasePressHandle = null;
    }, 170);
};

const clearShowcaseWaveTrail = () => {
    if (showcaseWaveTrailHandle) {
        clearTimeout(showcaseWaveTrailHandle);
        showcaseWaveTrailHandle = null;
    }
};

const triggerShowcaseWaveMotion = (direction) => {
    clearShowcaseWaveTrail();

    if (direction === 'left') {
        blueWaveLeftAnimation.value?.beginElement();
        orangeWaveLeftAnimation.value?.beginElement();
        showcaseWaveTrailHandle = setTimeout(() => {
            blueWaveBackLeftAnimation.value?.beginElement();
            orangeWaveBackLeftAnimation.value?.beginElement();
            blueWaveGlowLeftAnimation.value?.beginElement();
            orangeWaveGlowLeftAnimation.value?.beginElement();
            showcaseWaveTrailHandle = null;
        }, 120);
        return;
    }

    if (direction === 'right') {
        blueWaveRightAnimation.value?.beginElement();
        orangeWaveRightAnimation.value?.beginElement();
        showcaseWaveTrailHandle = setTimeout(() => {
            blueWaveBackRightAnimation.value?.beginElement();
            orangeWaveBackRightAnimation.value?.beginElement();
            blueWaveGlowRightAnimation.value?.beginElement();
            orangeWaveGlowRightAnimation.value?.beginElement();
            showcaseWaveTrailHandle = null;
        }, 120);
    }
};

const getShowcaseDirectionForIndex = (targetIndex) => {
    const totalSlides = showcaseSlides.value.length;

    if (totalSlides <= 1) {
        return null;
    }

    const normalizedTargetIndex = wrapSlideIndex(targetIndex);
    const currentIndex = activeShowcaseIndex.value;

    if (normalizedTargetIndex === currentIndex) {
        return null;
    }

    const forwardSteps = (normalizedTargetIndex - currentIndex + totalSlides) % totalSlides;
    const backwardSteps = (currentIndex - normalizedTargetIndex + totalSlides) % totalSlides;

    return forwardSteps <= backwardSteps ? 'right' : 'left';
};

const handleShowcaseDotClick = (index) => {
    const direction = getShowcaseDirectionForIndex(index);

    if (direction) {
        triggerShowcaseWaveMotion(direction);
    }

    setActiveShowcase(index);
    resumeShowcaseAutoplayIfAllowed();
};

const activateVisibleShowcase = (offset, key) => {
    pulseShowcaseCard(key);

    if (offset === 0) {
        resumeShowcaseAutoplayIfAllowed();
        return;
    }

    triggerShowcaseWaveMotion(offset > 0 ? 'right' : 'left');
    setActiveShowcase(activeShowcaseIndex.value + offset);
    resumeShowcaseAutoplayIfAllowed();
};

const goToNextShowcase = (options = {}) => {
    if (showcaseSlides.value.length <= 1) return;

    if (options.triggerWaveMotion) {
        triggerShowcaseWaveMotion('right');
    }

    setActiveShowcase(activeShowcaseIndex.value + 1);

    if (options.restartAutoplay) {
        resumeShowcaseAutoplayIfAllowed();
    }
};

const goToPreviousShowcase = (options = {}) => {
    if (showcaseSlides.value.length <= 1) return;

    if (options.triggerWaveMotion) {
        triggerShowcaseWaveMotion('left');
    }

    setActiveShowcase(activeShowcaseIndex.value - 1);

    if (options.restartAutoplay) {
        resumeShowcaseAutoplayIfAllowed();
    }
};

const stopShowcaseAutoplay = () => {
    if (autoplayHandle.value) {
        clearInterval(autoplayHandle.value);
        autoplayHandle.value = null;
    }
};

const startShowcaseAutoplay = () => {
    stopShowcaseAutoplay();

    if (showcaseSlides.value.length <= 1) {
        return;
    }

    autoplayHandle.value = setInterval(() => {
        goToNextShowcase();
    }, 4000);
};

const handleShowcaseMouseEnter = () => {
    isShowcaseHovered.value = true;
    stopShowcaseAutoplay();
};

const handleShowcaseMouseLeave = () => {
    isShowcaseHovered.value = false;
    startShowcaseAutoplay();
};

const pauseFeaturedVideo = () => {
    const playerWindow = featuredVideoIframe.value?.contentWindow;

    if (!playerWindow) {
        return;
    }

    playerWindow.postMessage(JSON.stringify({
        event: 'command',
        func: 'pauseVideo',
        args: [],
    }), 'https://www.youtube.com');
};

const stopObservingFeaturedVideo = () => {
    if (featuredVideoObserver) {
        featuredVideoObserver.disconnect();
        featuredVideoObserver = null;
    }
};

const startObservingFeaturedVideo = () => {
    stopObservingFeaturedVideo();

    if (!featuredVideoSection.value || !mainVideoEmbedUrl.value) {
        return;
    }

    featuredVideoObserver = new IntersectionObserver(
        ([entry]) => {
            if (!entry?.isIntersecting) {
                pauseFeaturedVideo();
            }
        },
        {
            threshold: 0.05,
        },
    );

    featuredVideoObserver.observe(featuredVideoSection.value);
};

const handlePageVisibilityChange = () => {
    if (document.hidden) {
        pauseFeaturedVideo();
    }
};

const getDesktopCarouselCardClass = (offset) => {
    if (offset === 0) {
        return 'z-30 left-1/2 top-[46%] h-[28.5rem] w-[19.25rem] -translate-x-1/2 -translate-y-1/2 scale-[1.02] opacity-100';
    }

    if (offset === -1) {
        return 'z-20 left-[28.5%] top-[46%] h-[24rem] w-[15.25rem] -translate-x-1/2 -translate-y-1/2 scale-[0.87] opacity-100';
    }

    if (offset === 1) {
        return 'z-20 left-[71.5%] top-[46%] h-[24rem] w-[15.25rem] -translate-x-1/2 -translate-y-1/2 scale-[0.87] opacity-100';
    }

    if (offset === -2) {
        return 'z-10 left-[10.5%] top-[46%] h-[22rem] w-[13.9rem] -translate-x-1/2 -translate-y-1/2 scale-[0.82] opacity-100';
    }

    return 'z-10 left-[89.5%] top-[46%] h-[22rem] w-[13.9rem] -translate-x-1/2 -translate-y-1/2 scale-[0.82] opacity-100';
};

const getDesktopCarouselImageClass = (offset) => {
    if (offset === 0) {
        return 'brightness-100';
    }

    if (Math.abs(offset) === 1) {
        return 'brightness-[0.7]';
    }

    return 'brightness-[0.58]';
};

const getMobileCarouselCardClass = (offset) => {
    if (offset === 0) {
        return 'z-30 left-1/2 top-[46%] h-[18.75rem] w-[13.25rem] -translate-x-1/2 -translate-y-1/2 scale-[1.02] opacity-100 shadow-[0_16px_36px_rgba(15,23,42,0.24)]';
    }

    if (offset === -1) {
        return 'z-20 left-[14.5%] top-[46%] h-[14.25rem] w-[8.75rem] -translate-x-1/2 -translate-y-1/2 scale-[0.86] opacity-100';
    }

    return 'z-20 left-[85.5%] top-[46%] h-[14.25rem] w-[8.75rem] -translate-x-1/2 -translate-y-1/2 scale-[0.86] opacity-100';
};

const getShowcaseCarouselCardClass = (offset) => (
    isMobileShowcaseViewport.value
        ? getMobileCarouselCardClass(offset)
        : getDesktopCarouselCardClass(offset)
);

const getShowcaseCarouselImageClass = (offset) => {
    if (isMobileShowcaseViewport.value) {
        return offset === 0 ? 'brightness-100' : 'brightness-[0.7]';
    }

    return getDesktopCarouselImageClass(offset);
};

watch(
    () => showcaseSlides.value.length,
    (length) => {
        if (!length) {
            activeShowcaseIndex.value = 0;
            showcaseDotWindowStart.value = 0;
            stopShowcaseAutoplay();
            return;
        }

        activeShowcaseIndex.value = wrapSlideIndex(activeShowcaseIndex.value);
        syncShowcaseDotWindow(activeShowcaseIndex.value);
        startShowcaseAutoplay();
    },
    { immediate: true },
);

watch(
    () => activeShowcaseIndex.value,
    (nextIndex, previousIndex) => {
        syncShowcaseDotWindow(nextIndex, previousIndex);
    },
);

watch(
    () => mainVideoEmbedUrl.value,
    () => {
        startObservingFeaturedVideo();
    },
);

onMounted(() => {
    updateShowcaseViewport();
    startShowcaseAutoplay();
    startObservingFeaturedVideo();
    document.addEventListener('visibilitychange', handlePageVisibilityChange);
    window.addEventListener('resize', updateShowcaseViewport);
    void measureResourceCategories();

    if (typeof ResizeObserver !== 'undefined') {
        resourceCategoriesResizeObserver = new ResizeObserver(() => {
            void measureResourceCategories();
        });

        observeResourceCategoriesGrid(resourceCategoriesGrid.value);
    }
});

onBeforeUnmount(() => {
    stopShowcaseAutoplay();
    stopObservingFeaturedVideo();
    document.removeEventListener('visibilitychange', handlePageVisibilityChange);
    window.removeEventListener('resize', updateShowcaseViewport);
    clearShowcaseWaveTrail();

    if (showcasePressHandle) {
        clearTimeout(showcasePressHandle);
    }

    resourceCategoriesResizeObserver?.disconnect();
});

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
    { immediate: true },
);

watch(
    () => props.folders?.map((folder) => `${folder.id}:${folder.name}:${folder.is_locked}`) ?? [],
    () => {
        void measureResourceCategories();
    },
    { deep: true },
);

watch(
    () => resourceCategoriesGrid.value,
    (grid, previousGrid) => {
        observeResourceCategoriesGrid(grid, previousGrid);
    },
);

</script>

<template>
    <Head title="Crystal Portal" />

    <UserLayout>
        <section class="relative isolate -mt-2 md:-mt-3">
            <div class="pointer-events-none absolute inset-y-0 left-1/2 w-screen -translate-x-1/2 overflow-hidden" aria-hidden="true">
                <div class="absolute inset-x-0 top-0 h-[46%] bg-[radial-gradient(circle_at_50%_18%,rgba(191,219,254,0.52)_0%,rgba(219,234,254,0.26)_34%,rgba(255,255,255,0)_72%)]" />

                <svg
                    class="absolute inset-x-0 top-[20%] h-[50%] w-full scale-[1.005] blur-[3px] md:top-[18%] md:h-[54%] md:blur-[4px]"
                    viewBox="0 0 1440 420"
                    preserveAspectRatio="none"
                >
                    <defs>
                        <linearGradient id="learning-resource-wave-blue" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#123499" />
                            <stop offset="48%" stop-color="#123499" />
                            <stop offset="100%" stop-color="#123499" />
                        </linearGradient>
                        <linearGradient id="learning-resource-wave-blue-highlight" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#4a6bd1" />
                            <stop offset="48%" stop-color="#6f8cf0" />
                            <stop offset="100%" stop-color="#4f72dc" />
                        </linearGradient>
                        <linearGradient id="learning-resource-wave-orange" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#ff7b00" />
                            <stop offset="42%" stop-color="#ff7b00" />
                            <stop offset="76%" stop-color="#ff7b00" />
                            <stop offset="100%" stop-color="#ff7b00" />
                        </linearGradient>
                        <linearGradient id="learning-resource-wave-orange-highlight" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#ffb15a" />
                            <stop offset="42%" stop-color="#ffc06f" />
                            <stop offset="76%" stop-color="#ffab47" />
                            <stop offset="100%" stop-color="#ffb861" />
                        </linearGradient>
                    </defs>
                    <path
                        :d="secondaryBlueWavePath"
                        fill="url(#learning-resource-wave-blue)"
                        fill-opacity="0.24"
                    >
                        <animate
                            ref="blueWaveBackLeftAnimation"
                            attributeName="d"
                            dur="1060ms"
                            begin="indefinite"
                            :values="`${secondaryBlueWavePath};${secondaryLeftBlueWavePath};${secondaryRightBlueWavePath};${secondaryBlueWavePath}`"
                            keyTimes="0;0.34;0.68;1"
                            calcMode="spline"
                            keySplines="0.22 1 0.36 1;0.42 0 0.2 1;0.22 1 0.36 1"
                        />
                        <animate
                            ref="blueWaveBackRightAnimation"
                            attributeName="d"
                            dur="1060ms"
                            begin="indefinite"
                            :values="`${secondaryBlueWavePath};${secondaryRightBlueWavePath};${secondaryLeftBlueWavePath};${secondaryBlueWavePath}`"
                            keyTimes="0;0.34;0.68;1"
                            calcMode="spline"
                            keySplines="0.22 1 0.36 1;0.42 0 0.2 1;0.22 1 0.36 1"
                        />
                    </path>
                    <path
                        :d="baseBlueWavePath"
                        fill="url(#learning-resource-wave-blue)"
                    >
                        <animate
                            ref="blueWaveLeftAnimation"
                            attributeName="d"
                            dur="980ms"
                            begin="indefinite"
                            :values="`${baseBlueWavePath};${leftBlueWavePath};${rightBlueWavePath};${baseBlueWavePath}`"
                            keyTimes="0;0.34;0.68;1"
                            calcMode="spline"
                            keySplines="0.22 1 0.36 1;0.42 0 0.2 1;0.22 1 0.36 1"
                        />
                        <animate
                            ref="blueWaveRightAnimation"
                            attributeName="d"
                            dur="980ms"
                            begin="indefinite"
                            :values="`${baseBlueWavePath};${rightBlueWavePath};${leftBlueWavePath};${baseBlueWavePath}`"
                            keyTimes="0;0.34;0.68;1"
                            calcMode="spline"
                            keySplines="0.22 1 0.36 1;0.42 0 0.2 1;0.22 1 0.36 1"
                        />
                    </path>
                    <path
                        :d="secondaryBlueWavePath"
                        fill="url(#learning-resource-wave-blue-highlight)"
                        fill-opacity="0.18"
                    >
                        <animate
                            ref="blueWaveGlowLeftAnimation"
                            attributeName="d"
                            dur="1120ms"
                            begin="indefinite"
                            :values="`${secondaryBlueWavePath};${secondaryLeftBlueWavePath};${secondaryRightBlueWavePath};${secondaryBlueWavePath}`"
                            keyTimes="0;0.34;0.68;1"
                            calcMode="spline"
                            keySplines="0.22 1 0.36 1;0.42 0 0.2 1;0.22 1 0.36 1"
                        />
                        <animate
                            ref="blueWaveGlowRightAnimation"
                            attributeName="d"
                            dur="1120ms"
                            begin="indefinite"
                            :values="`${secondaryBlueWavePath};${secondaryRightBlueWavePath};${secondaryLeftBlueWavePath};${secondaryBlueWavePath}`"
                            keyTimes="0;0.34;0.68;1"
                            calcMode="spline"
                            keySplines="0.22 1 0.36 1;0.42 0 0.2 1;0.22 1 0.36 1"
                        />
                    </path>
                    <path
                        :d="secondaryOrangeWavePath"
                        fill="url(#learning-resource-wave-orange)"
                        fill-opacity="0.3"
                    >
                        <animate
                            ref="orangeWaveBackLeftAnimation"
                            attributeName="d"
                            dur="1060ms"
                            begin="indefinite"
                            :values="`${secondaryOrangeWavePath};${secondaryLeftOrangeWavePath};${secondaryRightOrangeWavePath};${secondaryOrangeWavePath}`"
                            keyTimes="0;0.34;0.68;1"
                            calcMode="spline"
                            keySplines="0.22 1 0.36 1;0.42 0 0.2 1;0.22 1 0.36 1"
                        />
                        <animate
                            ref="orangeWaveBackRightAnimation"
                            attributeName="d"
                            dur="1060ms"
                            begin="indefinite"
                            :values="`${secondaryOrangeWavePath};${secondaryRightOrangeWavePath};${secondaryLeftOrangeWavePath};${secondaryOrangeWavePath}`"
                            keyTimes="0;0.34;0.68;1"
                            calcMode="spline"
                            keySplines="0.22 1 0.36 1;0.42 0 0.2 1;0.22 1 0.36 1"
                        />
                    </path>
                    <path
                        :d="baseOrangeWavePath"
                        fill="url(#learning-resource-wave-orange)"
                        fill-opacity="0.72"
                    >
                        <animate
                            ref="orangeWaveLeftAnimation"
                            attributeName="d"
                            dur="980ms"
                            begin="indefinite"
                            :values="`${baseOrangeWavePath};${leftOrangeWavePath};${rightOrangeWavePath};${baseOrangeWavePath}`"
                            keyTimes="0;0.34;0.68;1"
                            calcMode="spline"
                            keySplines="0.22 1 0.36 1;0.42 0 0.2 1;0.22 1 0.36 1"
                        />
                        <animate
                            ref="orangeWaveRightAnimation"
                            attributeName="d"
                            dur="980ms"
                            begin="indefinite"
                            :values="`${baseOrangeWavePath};${rightOrangeWavePath};${leftOrangeWavePath};${baseOrangeWavePath}`"
                            keyTimes="0;0.34;0.68;1"
                            calcMode="spline"
                            keySplines="0.22 1 0.36 1;0.42 0 0.2 1;0.22 1 0.36 1"
                        />
                    </path>
                    <path
                        :d="secondaryOrangeWavePath"
                        fill="url(#learning-resource-wave-orange-highlight)"
                        fill-opacity="0.16"
                    >
                        <animate
                            ref="orangeWaveGlowLeftAnimation"
                            attributeName="d"
                            dur="1120ms"
                            begin="indefinite"
                            :values="`${secondaryOrangeWavePath};${secondaryLeftOrangeWavePath};${secondaryRightOrangeWavePath};${secondaryOrangeWavePath}`"
                            keyTimes="0;0.34;0.68;1"
                            calcMode="spline"
                            keySplines="0.22 1 0.36 1;0.42 0 0.2 1;0.22 1 0.36 1"
                        />
                        <animate
                            ref="orangeWaveGlowRightAnimation"
                            attributeName="d"
                            dur="1120ms"
                            begin="indefinite"
                            :values="`${secondaryOrangeWavePath};${secondaryRightOrangeWavePath};${secondaryLeftOrangeWavePath};${secondaryOrangeWavePath}`"
                            keyTimes="0;0.34;0.68;1"
                            calcMode="spline"
                            keySplines="0.22 1 0.36 1;0.42 0 0.2 1;0.22 1 0.36 1"
                        />
                    </path>
                </svg>
            </div>

            <AppEmptyState
                v-if="showcaseSlides.length === 0"
                title="No featured resources yet"
                message="Carousel items added by admins will appear here."
            />

            <div
                v-else
                class="relative z-10 mt-2 md:mt-3"
                @mouseenter="handleShowcaseMouseEnter"
                @mouseleave="handleShowcaseMouseLeave"
            >
                <div class="relative h-[20.5rem] overflow-hidden sm:h-[22rem] md:h-[31.5rem]">
                    <button
                        v-for="item in visibleCarouselSlides"
                        :key="item.key"
                        type="button"
                        class="group absolute overflow-hidden rounded-[0.9rem] border border-white/80 bg-white text-left transition-all duration-700 ease-[cubic-bezier(0.22,1,0.36,1)] focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-4"
                        :class="getShowcaseCarouselCardClass(item.offset)"
                        :aria-label="`Show featured book ${item.slide.title}`"
                        @click="activateVisibleShowcase(item.offset, item.key)"
                    >
                        <div class="h-full w-full transition-transform duration-150 ease-out" :class="pressedShowcaseKey === item.key ? 'scale-[0.97]' : ''">
                            <img
                                :src="mediaUrl(item.slide.image_path)"
                                :alt="item.slide.title"
                                class="h-full w-full object-cover transition-[filter] duration-300"
                                :class="getShowcaseCarouselImageClass(item.offset)"
                            />
                            <div class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/82 via-slate-950/28 to-transparent px-3 pb-4 pt-12 opacity-100 transition-all duration-300 sm:px-4 sm:pb-5 sm:pt-16 md:translate-y-3 md:px-5 md:opacity-0 md:group-hover:translate-y-0 md:group-hover:opacity-100 md:group-focus-visible:translate-y-0 md:group-focus-visible:opacity-100">
                                <p class="line-clamp-2 text-xs font-black uppercase tracking-[0.12em] text-white sm:text-sm">
                                    {{ item.slide.title }}
                                </p>
                            </div>
                        </div>
                    </button>
                </div>

                <div v-if="showcaseSlides.length > 1" class="relative mt-3 flex items-center justify-center gap-2.5 sm:gap-3 md:-mt-6 md:gap-4">
                    <button
                        type="button"
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200/80 bg-white/96 text-slate-700 transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-200 hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 md:h-11 md:w-11"
                        aria-label="Previous featured book"
                        @click="goToPreviousShowcase({ restartAutoplay: true, triggerWaveMotion: true })"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M11.75 4.5 6.25 10l5.5 5.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" />
                        </svg>
                    </button>

                    <div class="flex items-center justify-center gap-1.5 rounded-full border border-slate-200/70 bg-white/96 px-3 py-2.5 sm:gap-2 sm:px-4 sm:py-3">
                        <button
                            v-for="dot in visibleShowcaseDots"
                            :key="dot.id"
                            type="button"
                            class="h-2.5 w-2.5 rounded-full transition-all duration-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                            :class="dot.index === activeShowcaseIndex ? 'scale-125 bg-slate-950 shadow-[0_0_0_6px_rgba(59,130,246,0.14)]' : 'bg-slate-300 hover:bg-slate-400'"
                            :aria-label="`Go to featured book ${dot.index + 1}`"
                            :aria-current="dot.index === activeShowcaseIndex ? 'true' : 'false'"
                            @click="handleShowcaseDotClick(dot.index)"
                        />
                    </div>

                    <button
                        type="button"
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200/80 bg-white/96 text-slate-700 transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-200 hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 md:h-11 md:w-11"
                        aria-label="Next featured book"
                        @click="goToNextShowcase({ restartAutoplay: true, triggerWaveMotion: true })"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M8.25 4.5 13.75 10l-5.5 5.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" />
                        </svg>
                    </button>
                </div>
            </div>
        </section>

        <section id="resource-categories" ref="resourceCategoriesSection" class="mt-10 scroll-mt-16 rounded-[2rem] border-2 border-slate-200 bg-white px-4 py-6 shadow-[0_18px_35px_rgba(15,23,42,0.07)] md:scroll-mt-20 md:px-6 md:py-7">
            <div class="mb-6 text-center">
                <h2 class="text-2xl font-black uppercase tracking-tight text-slate-950">Resource Categories</h2>
            </div>

            <AppEmptyState
                v-if="folders.length === 0"
                title="No resource categories yet"
                message="Once folders are published by admins, they will appear here as learning resource categories."
            />

            <div v-else>
                <div
                    class="resource-categories-panel relative overflow-hidden"
                    :style="resourceCategoriesContainerStyle"
                >
                    <div ref="resourceCategoriesGrid" class="grid grid-cols-2 gap-3 md:grid-cols-2 md:gap-4 xl:grid-cols-3">
                        <UserFolderItem
                            v-for="folder in folders"
                            :key="folder.id"
                            :folder="folder"
                            :is-root="true"
                        />
                    </div>
                </div>
            </div>

            <div v-if="canExpandResourceCategories" class="mt-5 flex justify-center">
                <button
                    type="button"
                    class="action-btn-primary min-w-[11.5rem] justify-center px-5 py-2.5 text-[11px] font-black uppercase tracking-[0.16em]"
                    @click="toggleResourceCategories"
                >
                    {{ isResourceCategoriesExpanded ? 'Show Less' : 'View All Resources' }}
                </button>
            </div>
        </section>

        <section ref="featuredVideoSection" class="mt-10">
            <div class="rounded-[2rem] border-2 border-slate-200 bg-white p-4 shadow-[0_18px_35px_rgba(15,23,42,0.07)] md:p-6">
                <AppEmptyState
                    v-if="!mainVideo"
                    title="No featured video yet"
                    message="When admins publish a featured video, it will appear here."
                />

                <div v-else class="grid gap-6 lg:grid-cols-[1fr_1.4fr] lg:items-center">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-4">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                                <ApplicationLogo class="h-8 w-8 fill-current" />
                            </div>
                            <div>
                                <p class="text-sm font-black text-slate-900">DEPED</p>
                                <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-600">Ozamiz - CRYSTAL Portal</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <h3 class="text-base font-black text-slate-900">Comprehensive Resource for Year-Round Systemazide Teaching and Learning</h3>
                            <p class="text-sm font-medium leading-6 text-slate-600">
                                {{ mainVideo.description || 'Featured classroom support video for teacher reference and resource-based learning.' }}
                            </p>
                        </div>

                        <a :href="mainVideo.youtube_link" class="action-btn-primary w-full justify-center">
                            Watch Video
                        </a>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-black shadow-lg">
                        <iframe
                            v-if="mainVideoEmbedUrl"
                            ref="featuredVideoIframe"
                            :src="mainVideoEmbedUrl"
                            class="aspect-video w-full"
                            title="Featured video player"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen
                        />
                        <div v-else class="aspect-video w-full bg-slate-900" />
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-4 -mb-4 md:-mb-6" aria-labelledby="portal-links-heading">
            <h2 id="portal-links-heading" class="sr-only">DepEd systems and official links</h2>

            <PortalLinkSection
                title="DepEd Systems and Official websites"
                eyebrow=""
                :items="officialLinks"
                :mobile-paired-items="depedSystemsLinks"
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
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.resource-categories-panel {
    transform: translateZ(0);
    will-change: max-height;
    transition: max-height 620ms cubic-bezier(0.22, 1, 0.36, 1);
}

@media (prefers-reduced-motion: reduce) {
    .resource-categories-panel {
        transition-duration: 1ms;
    }
}

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
