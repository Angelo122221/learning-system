<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Chart, registerables } from 'chart.js';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

Chart.register(...registerables);

const props = defineProps({
    stats: Object,
    kpis: Object,
    teacherAnalytics: Object,
    districtAnalytics: Object,
    resourceAnalytics: Object,
    materialAnalytics: Object,
    filters: Object,
    filterOptions: Object,
    districtStats: Array,
    schoolStats: Array,
    topFolders: Array,
    topFiles: Array,
    recentActivity: Array,
    usersByRole: Array,
    loadError: String,
});

const filterForm = ref({
    district: props.filters?.district ?? '',
    school: props.filters?.school ?? '',
    category: props.filters?.category ?? '',
    search: props.filters?.search ?? '',
});

watch(
    () => props.filters,
    (nextFilters) => {
        filterForm.value = {
            district: nextFilters?.district ?? '',
            school: nextFilters?.school ?? '',
            category: nextFilters?.category ?? '',
            search: nextFilters?.search ?? '',
        };
    },
    { deep: true },
);

watch(
    () => filterForm.value.district,
    () => {
        const schools = Array.isArray(props.filterOptions?.schools) ? props.filterOptions.schools : [];
        if (!schools.includes(filterForm.value.school)) {
            filterForm.value.school = '';
        }
    },
);

const districtOptions = computed(() => {
    return Array.isArray(props.filterOptions?.districts) ? props.filterOptions.districts : [];
});

const schoolOptions = computed(() => {
    return Array.isArray(props.filterOptions?.schools) ? props.filterOptions.schools : [];
});

const categoryOptions = computed(() => {
    return Array.isArray(props.filterOptions?.categories) ? props.filterOptions.categories : [];
});

const hasActiveFilters = computed(() => {
    return Boolean(filterForm.value.district || filterForm.value.school || filterForm.value.category || filterForm.value.search);
});

const applyFilters = () => {
    router.get('/admin/analytics', {
        district: filterForm.value.district || undefined,
        school: filterForm.value.school || undefined,
        category: filterForm.value.category || undefined,
        search: filterForm.value.search || undefined,
    }, {
        preserveScroll: true,
        replace: true,
    });
};

const clearFilters = () => {
    filterForm.value = {
        district: '',
        school: '',
        category: '',
        search: '',
    };

    applyFilters();
};

const topTeachersChartRef = ref(null);
const opensVsDownloadsChartRef = ref(null);
const teacherTrendChartRef = ref(null);
const districtHeatmapChartRef = ref(null);
const districtRadarChartRef = ref(null);
const resourceCategoriesChartRef = ref(null);
const resourceUsageTrendChartRef = ref(null);

const materialTeacherHoldingsChartRef = ref(null);
const materialSchoolHoldingsChartRef = ref(null);
const materialResourceTypeChartRef = ref(null);
const materialLearningAreaChartRef = ref(null);
const materialTrendChartRef = ref(null);

const chartInstances = [];

const chartPalette = {
    blue: '#2563eb',
    emerald: '#10b981',
    amber: '#f59e0b',
    red: '#ef4444',
    slate: '#64748b',
    grid: 'rgba(148, 163, 184, 0.28)',
};

const teacherChartBarPalette = [
    '#2563eb',
    '#10b981',
    '#f59e0b',
    '#ef4444',
    '#8b5cf6',
    '#06b6d4',
    '#f97316',
    '#84cc16',
    '#ec4899',
    '#6366f1',
];

const teacherChartBorderPalette = [
    '#1d4ed8',
    '#059669',
    '#d97706',
    '#dc2626',
    '#7c3aed',
    '#0891b2',
    '#ea580c',
    '#65a30d',
    '#db2777',
    '#4f46e5',
];

const categoryChartPalette = [
    chartPalette.blue,
    chartPalette.emerald,
    chartPalette.amber,
    chartPalette.red,
    '#8b5cf6',
    '#06b6d4',
    '#f97316',
    '#84cc16',
    '#ec4899',
    '#6366f1',
];

const PROGRESSIVE_LINE_TOTAL_DURATION = 10000;

const buildProgressiveLineAnimation = (pointCount, startValue = 100) => {
    const normalizedPointCount = Math.max(Number(pointCount) || 0, 1);
    const delayBetweenPoints = PROGRESSIVE_LINE_TOTAL_DURATION / normalizedPointCount;

    const previousY = (ctx) => {
        if (ctx.index === 0) {
            return ctx.chart.scales.y.getPixelForValue(startValue);
        }

        const previousPoint = ctx.chart
            .getDatasetMeta(ctx.datasetIndex)
            .data[ctx.index - 1];

        return previousPoint?.getProps(['y'], true).y
            ?? ctx.chart.scales.y.getPixelForValue(startValue);
    };

    return {
        x: {
            type: 'number',
            easing: 'linear',
            duration: delayBetweenPoints,
            from: Number.NaN,
            delay(ctx) {
                if (ctx.type !== 'data' || ctx.xStarted) {
                    return 0;
                }

                ctx.xStarted = true;
                return ctx.index * delayBetweenPoints;
            },
        },
        y: {
            type: 'number',
            easing: 'linear',
            duration: delayBetweenPoints,
            from: previousY,
            delay(ctx) {
                if (ctx.type !== 'data' || ctx.yStarted) {
                    return 0;
                }

                ctx.yStarted = true;
                return ctx.index * delayBetweenPoints;
            },
        },
    };
};

const numberFormatter = new Intl.NumberFormat('en-US');
const formatNumber = (value) => numberFormatter.format(Number(value ?? 0));

const toNumber = (value) => {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : 0;
};

const normalizeText = (value, fallback = 'N/A') => {
    const text = String(value ?? '').trim();
    return text === '' ? fallback : text;
};

const resolvedKpis = computed(() => {
    const source = props.kpis ?? {};
    const stats = props.stats ?? {};

    return {
        totalTeachers: toNumber(source.totalTeachers),
        totalSchools: toNumber(source.totalSchools),
        totalDistricts: toNumber(source.totalDistricts),
        totalFolders: toNumber(source.totalFolders),
        totalResources: toNumber(source.totalResources),
        totalDownloads: toNumber(source.totalDownloads ?? stats.total_downloads),
        totalOpens: toNumber(source.totalOpens ?? stats.total_file_opens),
        totalLockedResources: toNumber(source.totalLockedResources),
    };
});

const kpiCards = computed(() => {
    const kpis = resolvedKpis.value;

    return [
        { key: 'totalTeachers', label: 'Total Teachers', value: kpis.totalTeachers, trend: null, tone: 'blue', icon: 'users' },
        { key: 'totalSchools', label: 'Total Schools', value: kpis.totalSchools, trend: null, tone: 'blue', icon: 'building' },
        { key: 'totalDistricts', label: 'Total Districts', value: kpis.totalDistricts, trend: null, tone: 'slate', icon: 'map' },
        { key: 'totalFolders', label: 'Total Folders', value: kpis.totalFolders, trend: null, tone: 'blue', icon: 'folder' },
        { key: 'totalResources', label: 'Total Resources', value: kpis.totalResources, trend: null, tone: 'blue', icon: 'stack' },
        { key: 'totalDownloads', label: 'Total Downloads', value: kpis.totalDownloads, trend: null, tone: 'emerald', icon: 'download' },
        { key: 'totalOpens', label: 'Total Opens', value: kpis.totalOpens, trend: null, tone: 'emerald', icon: 'eye' },
        { key: 'totalLockedResources', label: 'Total Locked Resources', value: kpis.totalLockedResources, trend: null, tone: 'red', icon: 'lock' },
    ];
});

const resolvedTeacherAnalytics = computed(() => props.teacherAnalytics ?? {});
const resolvedDistrictAnalytics = computed(() => props.districtAnalytics ?? {});
const resolvedResourceAnalytics = computed(() => props.resourceAnalytics ?? {});
const resolvedMaterialAnalytics = computed(() => props.materialAnalytics ?? {});

const teacherActivityData = computed(() => {
    return (resolvedTeacherAnalytics.value.topActiveTeachers ?? []).map((row) => ({
        name: normalizeText(row.teacherName, 'Unknown Teacher'),
        district: normalizeText(row.district, 'Unknown District'),
        school: normalizeText(row.school, 'Unknown School'),
        opens: toNumber(row.opens),
        downloads: toNumber(row.downloads),
        totalActivity: toNumber(row.totalActivity),
    }));
});

const teacherOpensTotal = computed(() => {
    const value = resolvedTeacherAnalytics.value.opensVsDownloads?.opens;
    if (value !== undefined && value !== null) {
        return toNumber(value);
    }

    return teacherActivityData.value.reduce((sum, row) => sum + row.opens, 0);
});

const teacherDownloadsTotal = computed(() => {
    const value = resolvedTeacherAnalytics.value.opensVsDownloads?.downloads;
    if (value !== undefined && value !== null) {
        return toNumber(value);
    }

    return teacherActivityData.value.reduce((sum, row) => sum + row.downloads, 0);
});

const teacherTrendPeriod = ref('monthly');
const teacherTrendDateFrom = ref('');
const teacherTrendDateTo = ref('');

const teacherTrendPeriodOptions = [
    { value: 'daily', label: 'Daily' },
    { value: 'weekly', label: 'Weekly' },
    { value: 'monthly', label: 'Monthly' },
    { value: 'yearly', label: 'Yearly' },
];

const teacherTrendPeriodLabel = computed(() => {
    const selected = teacherTrendPeriodOptions.find((option) => option.value === teacherTrendPeriod.value);
    return selected?.label ?? 'Monthly';
});

const rawTeacherTrend = computed(() => {
    const rows = resolvedTeacherAnalytics.value.monthlyTrend ?? [];
    return rows
        .map((row) => {
            const rawKey = normalizeText(row.dateKey ?? row.monthKey ?? row.label, '');
            const parsed = new Date(rawKey);
            if (rawKey === '' || Number.isNaN(parsed.getTime())) {
                return null;
            }

            return {
                date: parsed,
                opens: toNumber(row.opens),
                downloads: toNumber(row.downloads),
            };
        })
        .filter(Boolean);
});

const filteredTeacherTrend = computed(() => {
    const fromDate = teacherTrendDateFrom.value ? new Date(`${teacherTrendDateFrom.value}T00:00:00`) : null;
    const toDate = teacherTrendDateTo.value ? new Date(`${teacherTrendDateTo.value}T23:59:59`) : null;

    return rawTeacherTrend.value.filter((row) => {
        if (fromDate && row.date < fromDate) {
            return false;
        }
        if (toDate && row.date > toDate) {
            return false;
        }
        return true;
    });
});

const teacherTrendSeries = computed(() => {
    const bucket = new Map();
    const monthlyFormatter = new Intl.DateTimeFormat('en-US', { month: 'short', year: 'numeric' });

    for (const row of filteredTeacherTrend.value) {
        const date = row.date;
        let key = '';
        let label = '';

        if (teacherTrendPeriod.value === 'daily') {
            const isoDate = date.toISOString().slice(0, 10);
            key = isoDate;
            label = new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(date);
        } else if (teacherTrendPeriod.value === 'weekly') {
            const weekStart = new Date(date);
            const day = weekStart.getDay();
            const diff = day === 0 ? -6 : 1 - day;
            weekStart.setDate(weekStart.getDate() + diff);
            const isoDate = weekStart.toISOString().slice(0, 10);
            key = isoDate;
            label = `Week of ${new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric' }).format(weekStart)}`;
        } else if (teacherTrendPeriod.value === 'yearly') {
            key = String(date.getFullYear());
            label = key;
        } else {
            const month = String(date.getMonth() + 1).padStart(2, '0');
            key = `${date.getFullYear()}-${month}`;
            label = monthlyFormatter.format(date);
        }

        const current = bucket.get(key) ?? { label, opens: 0, downloads: 0 };
        current.opens += row.opens;
        current.downloads += row.downloads;
        bucket.set(key, current);
    }

    const ordered = [...bucket.entries()]
        .sort((a, b) => a[0].localeCompare(b[0]))
        .map(([, value]) => value);

    return {
        labels: ordered.map((row) => row.label),
        opens: ordered.map((row) => row.opens),
        downloads: ordered.map((row) => row.downloads),
    };
});

const leastActiveTeachers = computed(() => {
    return (resolvedTeacherAnalytics.value.leastActiveTeachers ?? []).map((row) => ({
        name: normalizeText(row.teacherName, 'Unknown Teacher'),
        district: normalizeText(row.district, 'Unknown District'),
        school: normalizeText(row.school, 'Unknown School'),
        opens: toNumber(row.opens),
        downloads: toNumber(row.downloads),
        total: toNumber(row.totalActivity),
        status: 'Least Active',
    }));
});

const noActivityTeachers = computed(() => {
    return (resolvedTeacherAnalytics.value.noActivityTeachers ?? []).map((row) => ({
        name: normalizeText(row.teacherName, 'Unknown Teacher'),
        district: normalizeText(row.district, 'Unknown District'),
        school: normalizeText(row.school, 'Unknown School'),
        opens: toNumber(row.opens),
        downloads: toNumber(row.downloads),
        total: 0,
        status: 'No Activity',
    }));
});

const leastAndNoActivityRows = computed(() => [...leastActiveTeachers.value, ...noActivityTeachers.value]);

const districtPerformance = computed(() => {
    return (resolvedDistrictAnalytics.value.mostActiveDistricts ?? []).map((row) => ({
        district: normalizeText(row.district, 'Unknown District'),
        totalTeachers: toNumber(row.totalTeachers),
        opens: toNumber(row.opens),
        downloads: toNumber(row.downloads),
        totalActivity: toNumber(row.totalActivity),
    }));
});

const schoolPerformance = computed(() => {
    return (resolvedDistrictAnalytics.value.mostActiveSchools ?? []).map((row) => ({
        district: normalizeText(row.district, 'Unknown District'),
        school: normalizeText(row.school, 'Unknown School'),
        totalTeachers: toNumber(row.totalTeachers),
        opens: toNumber(row.opens),
        downloads: toNumber(row.downloads),
        totalActivity: toNumber(row.totalActivity),
    }));
});

const districtLeaderboardRows = computed(() => {
    return (resolvedDistrictAnalytics.value.leaderboard ?? []).map((row) => ({
        district: normalizeText(row.district, 'Unknown District'),
        school: normalizeText(row.school, 'Unknown School'),
        totalTeachers: toNumber(row.totalTeachers),
        opens: toNumber(row.opens),
        downloads: toNumber(row.downloads),
        totalActivity: toNumber(row.totalActivity),
    }));
});

const districtRadarSelection = ref([]);

const districtRadarDistrictOptions = computed(() => {
    const fromFilters = Array.isArray(props.filterOptions?.districts) ? props.filterOptions.districts : [];
    const fromData = [
        ...districtPerformance.value.map((row) => row.district),
        ...districtHeatmap.value.map((row) => row.district),
        ...districtLeaderboardRows.value.map((row) => row.district),
    ];

    const merged = [...new Set([...fromFilters, ...fromData].map((value) => normalizeText(value, 'Unknown District')))]
        .filter((value) => value !== 'Unknown District')
        .sort((a, b) => a.localeCompare(b));

    return merged;
});

const districtRadarSelectionLabel = computed(() => {
    const selected = Array.isArray(districtRadarSelection.value) ? districtRadarSelection.value : [];
    if (selected.length === 0) {
        return 'Top districts';
    }

    if (selected.length === 1) {
        return selected[0];
    }

    return `${selected.length} selected`;
});

const resolveDistrictRadarRow = (districtName) => {
    if (!districtName) {
        return null;
    }

    const match = (row) => normalizeText(row?.district, '') === districtName;

    const fromHeatmap = districtHeatmap.value.find(match);
    if (fromHeatmap) {
        return fromHeatmap;
    }

    const fromPerformance = districtPerformance.value.find(match);
    if (fromPerformance) {
        return fromPerformance;
    }

    // Fallback: aggregate from leaderboard rows (district + school entries).
    const aggregated = districtLeaderboardRows.value
        .filter((row) => normalizeText(row.district, '') === districtName)
        .reduce((acc, row) => {
            acc.totalTeachers += toNumber(row.totalTeachers);
            acc.opens += toNumber(row.opens);
            acc.downloads += toNumber(row.downloads);
            acc.totalActivity += toNumber(row.totalActivity);
            return acc;
        }, {
            district: districtName,
            totalTeachers: 0,
            opens: 0,
            downloads: 0,
            totalActivity: 0,
        });

    return aggregated;
};

const districtRadar = computed(() => {
    const metrics = [
        { key: 'totalTeachers', label: 'Teachers' },
        { key: 'opens', label: 'Opens' },
        { key: 'downloads', label: 'Downloads' },
        { key: 'totalActivity', label: 'Total' },
    ];

    const selectedDistrictNames = (Array.isArray(districtRadarSelection.value) ? districtRadarSelection.value : [])
        .map((name) => normalizeText(name, ''))
        .filter(Boolean);

    const selectedRows = selectedDistrictNames
        .map((districtName) => resolveDistrictRadarRow(districtName))
        .filter(Boolean)
        .sort((a, b) => toNumber(b.totalActivity) - toNumber(a.totalActivity))
        .slice(0, 6);

    const rows = selectedRows.length
        ? selectedRows
        : [...districtHeatmap.value]
            .sort((a, b) => b.totalActivity - a.totalActivity)
            .slice(0, 6);

    const allRowsForScale = districtHeatmap.value.length ? districtHeatmap.value : districtPerformance.value;

    const maxByMetric = metrics.reduce((acc, metric) => {
        const max = Math.max(1, ...allRowsForScale.map((row) => toNumber(row?.[metric.key])));
        acc[metric.key] = max;
        return acc;
    }, {});

    const radarPalette = [
        { border: chartPalette.blue, fill: 'rgba(37, 99, 235, 0.14)' },
        { border: chartPalette.emerald, fill: 'rgba(16, 185, 129, 0.12)' },
        { border: chartPalette.amber, fill: 'rgba(245, 158, 11, 0.14)' },
        { border: chartPalette.red, fill: 'rgba(239, 68, 68, 0.12)' },
        { border: '#8b5cf6', fill: 'rgba(139, 92, 246, 0.12)' },
        { border: '#06b6d4', fill: 'rgba(6, 182, 212, 0.12)' },
    ];

    const datasets = rows.map((row, index) => {
        const palette = radarPalette[index % radarPalette.length];

        // Normalize per metric to a shared 0-100 scale.
        const data = metrics.map((metric) => {
            const raw = toNumber(row?.[metric.key]);
            const max = toNumber(maxByMetric[metric.key]) || 1;
            return Math.round((raw / max) * 100);
        });

        return {
            label: row?.district ?? 'District',
            data,
            borderColor: palette.border,
            backgroundColor: palette.fill,
            pointBackgroundColor: palette.border,
            pointBorderColor: '#ffffff',
            pointHoverBorderColor: '#ffffff',
            pointRadius: 2.5,
            pointHoverRadius: 4,
            borderWidth: 2,
            fill: true,
            // Keep raw values for tooltips.
            _raw: row,
        };
    });

    return {
        labels: metrics.map((metric) => metric.label),
        datasets,
    };
});

const districtRadarLineData = computed(() => {
    const selectedDistrictNames = (Array.isArray(districtRadarSelection.value) ? districtRadarSelection.value : [])
        .map((name) => normalizeText(name, ''))
        .filter(Boolean);

    const selectedRows = selectedDistrictNames
        .map((districtName) => resolveDistrictRadarRow(districtName))
        .filter(Boolean)
        .sort((a, b) => toNumber(b.totalActivity) - toNumber(a.totalActivity));

    const rows = selectedRows.length
        ? selectedRows
        : [...districtHeatmap.value]
            .sort((a, b) => b.totalActivity - a.totalActivity)
            .slice(0, 10);

    const linePalette = [
        chartPalette.blue,
        chartPalette.emerald,
        chartPalette.amber,
        chartPalette.red,
        '#8b5cf6',
        '#06b6d4',
        '#f97316',
        '#84cc16',
        '#ec4899',
        '#6366f1',
    ];

    return {
        labels: rows.map((row, index) => `${index + 1}`),
        districts: rows,
        datasets: [
            {
                label: 'District Activity',
                data: rows.map((row) => row.totalActivity),
                borderColor: linePalette[0],
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                borderWidth: 3,
                fill: false,
                tension: 0.4,
                pointBackgroundColor: linePalette[0],
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8,
            },
        ],
    };
});

const districtHeatmap = computed(() => {
    const source = (resolvedDistrictAnalytics.value.heatmap ?? districtPerformance.value)
        .map((row) => ({
            district: normalizeText(row.district, 'Unknown District'),
            totalTeachers: toNumber(row.totalTeachers),
            opens: toNumber(row.opens),
            downloads: toNumber(row.downloads),
            totalActivity: toNumber(row.totalActivity),
        }));

    const maxTotal = Math.max(1, ...source.map((row) => row.totalActivity));

    return source.map((row) => ({
        ...row,
        intensity: Math.max(0.2, row.totalActivity / maxTotal),
    }));
});

const categoryPopularity = computed(() => {
    return (resolvedResourceAnalytics.value.categoryPopularity ?? []).map((row) => ({
        category: normalizeText(row.category, 'Uncategorized'),
        total: toNumber(row.total),
    }));
});

const categoryUsageTotal = computed(() => {
    return categoryPopularity.value.reduce((sum, row) => sum + toNumber(row.total), 0);
});

const monthlyResourceUsage = computed(() => {
    const rows = resolvedResourceAnalytics.value.monthlyUsageTrend ?? [];

    return {
        labels: rows.map((row) => normalizeText(row.label, 'Unknown')),
        opens: rows.map((row) => toNumber(row.opens)),
        downloads: rows.map((row) => toNumber(row.downloads)),
    };
});

const resourcesNeverOpened = computed(() => {
    return (resolvedResourceAnalytics.value.neverOpenedResources ?? []).map((row) => ({
        title: normalizeText(row.title, 'Untitled Resource'),
        category: normalizeText(row.category, 'Uncategorized'),
        folder: normalizeText(row.folder, 'Unassigned Folder'),
        uploadedAt: normalizeText(row.uploadedAt, 'N/A'),
    }));
});

const lockedResourcesWithActivity = computed(() => {
    return (resolvedResourceAnalytics.value.lockedHighActivityResources ?? []).map((row) => ({
        title: normalizeText(row.title, 'Untitled Resource'),
        category: normalizeText(row.category, 'Uncategorized'),
        opens: toNumber(row.opens),
        downloads: toNumber(row.downloads),
        totalActivity: toNumber(row.totalActivity),
    }));
});

const materialStats = computed(() => {
    const source = resolvedMaterialAnalytics.value.stats ?? {};

    return {
        totalMaterials: toNumber(source.totalMaterials),
        totalSubmissions: toNumber(source.totalSubmissions),
        totalQuantity: toNumber(source.totalQuantity),
        teachersWithInventory: toNumber(source.teachersWithInventory),
        schoolsWithInventory: toNumber(source.schoolsWithInventory),
        districtsWithInventory: toNumber(source.districtsWithInventory),
    };
});

const materialStatCards = computed(() => {
    const stats = materialStats.value;

    return [
        { key: 'totalMaterials', label: 'Materials', value: stats.totalMaterials, tone: 'blue' },
        { key: 'totalSubmissions', label: 'Submissions', value: stats.totalSubmissions, tone: 'emerald' },
        { key: 'totalQuantity', label: 'Total Quantity', value: stats.totalQuantity, tone: 'blue' },
        { key: 'teachersWithInventory', label: 'Teachers Reporting', value: stats.teachersWithInventory, tone: 'slate' },
        { key: 'schoolsWithInventory', label: 'Schools Reporting', value: stats.schoolsWithInventory, tone: 'slate' },
        { key: 'districtsWithInventory', label: 'Districts Reporting', value: stats.districtsWithInventory, tone: 'amber' },
    ];
});

const materialTeacherHoldings = computed(() => {
    return (resolvedMaterialAnalytics.value.teacherHoldings ?? []).map((row) => ({
        teacherName: normalizeText(row.teacherName, 'Unknown Teacher'),
        district: normalizeText(row.district, 'Unknown District'),
        school: normalizeText(row.school, 'Unknown School'),
        totalMaterials: toNumber(row.totalMaterials),
        totalQuantity: toNumber(row.totalQuantity),
    }));
});

const materialSchoolHoldings = computed(() => {
    return (resolvedMaterialAnalytics.value.schoolHoldings ?? []).map((row) => ({
        district: normalizeText(row.district, 'Unknown District'),
        school: normalizeText(row.school, 'Unknown School'),
        totalTeachers: toNumber(row.totalTeachers),
        totalMaterials: toNumber(row.totalMaterials),
        totalQuantity: toNumber(row.totalQuantity),
    }));
});

const materialResourceTypeBreakdown = computed(() => {
    return (resolvedMaterialAnalytics.value.resourceTypeBreakdown ?? []).map((row) => ({
        resourceType: normalizeText(row.resourceType, 'Unspecified'),
        totalMaterials: toNumber(row.totalMaterials),
        totalSubmissions: toNumber(row.totalSubmissions),
        totalQuantity: toNumber(row.totalQuantity),
    }));
});

const materialLearningAreaBreakdown = computed(() => {
    return (resolvedMaterialAnalytics.value.learningAreaBreakdown ?? []).map((row) => ({
        learningArea: normalizeText(row.learningArea, 'Unspecified'),
        totalMaterials: toNumber(row.totalMaterials),
        totalSubmissions: toNumber(row.totalSubmissions),
        totalQuantity: toNumber(row.totalQuantity),
    }));
});

const materialGradeLevelBreakdown = computed(() => {
    return (resolvedMaterialAnalytics.value.gradeLevelBreakdown ?? []).map((row) => ({
        gradeLevel: normalizeText(row.gradeLevel, 'Unspecified'),
        totalMaterials: toNumber(row.totalMaterials),
        totalSubmissions: toNumber(row.totalSubmissions),
        totalQuantity: toNumber(row.totalQuantity),
    }));
});

const materialMonthlyTrend = computed(() => {
    const rows = resolvedMaterialAnalytics.value.monthlyInventoryTrend ?? [];

    return {
        labels: rows.map((row) => normalizeText(row.label, 'Unknown')),
        quantities: rows.map((row) => toNumber(row.totalQuantity)),
        submissions: rows.map((row) => toNumber(row.totalSubmissions)),
    };
});

const topMaterialsByQuantity = computed(() => {
    return (resolvedMaterialAnalytics.value.topMaterialsByQuantity ?? []).map((row) => ({
        materialName: normalizeText(row.materialName, 'Unknown Material'),
        author: normalizeText(row.author, 'N/A'),
        resourceType: normalizeText(row.resourceType, 'Unspecified'),
        learningArea: normalizeText(row.learningArea, 'Unspecified'),
        gradeLevel: normalizeText(row.gradeLevel, 'Unspecified'),
        publisher: normalizeText(row.publisher, 'N/A'),
        teacherCount: toNumber(row.teacherCount),
        totalSubmissions: toNumber(row.totalSubmissions),
        totalQuantity: toNumber(row.totalQuantity),
        updatedAt: normalizeText(row.updatedAt, 'N/A'),
    }));
});

const unreportedMaterials = computed(() => {
    return (resolvedMaterialAnalytics.value.unreportedMaterials ?? []).map((row) => ({
        materialName: normalizeText(row.materialName, 'Unknown Material'),
        author: normalizeText(row.author, 'N/A'),
        resourceType: normalizeText(row.resourceType, 'Unspecified'),
        learningArea: normalizeText(row.learningArea, 'Unspecified'),
        gradeLevel: normalizeText(row.gradeLevel, 'Unspecified'),
        publisher: normalizeText(row.publisher, 'N/A'),
    }));
});

const toneClasses = {
    blue: {
        accent: 'border-blue-500',
        badge: 'bg-blue-50 text-blue-700',
        trendUp: 'text-emerald-600',
        trendDown: 'text-red-500',
    },
    emerald: {
        accent: 'border-emerald-500',
        badge: 'bg-emerald-50 text-emerald-700',
        trendUp: 'text-emerald-600',
        trendDown: 'text-red-500',
    },
    red: {
        accent: 'border-red-500',
        badge: 'bg-red-50 text-red-700',
        trendUp: 'text-emerald-600',
        trendDown: 'text-red-500',
    },
    amber: {
        accent: 'border-amber-500',
        badge: 'bg-amber-50 text-amber-700',
        trendUp: 'text-emerald-600',
        trendDown: 'text-red-500',
    },
    slate: {
        accent: 'border-slate-400',
        badge: 'bg-slate-100 text-slate-700',
        trendUp: 'text-emerald-600',
        trendDown: 'text-red-500',
    },
};

const kpiToneClass = (tone) => toneClasses[tone] ?? toneClasses.slate;

const iconPaths = {
    users: 'M16 11c1.66 0 3-1.57 3-3.5S17.66 4 16 4s-3 1.57-3 3.5 1.34 3.5 3 3.5zm-8 0c1.66 0 3-1.57 3-3.5S9.66 4 8 4 5 5.57 5 7.5 6.34 11 8 11zm0 2c-2.67 0-8 1.34-8 4v3h16v-3c0-2.66-5.33-4-8-4zm8 0c-.29 0-.62.02-.97.05 1.34.97 2.22 2.23 2.22 3.95v3h6v-3c0-2.66-5.33-4-8-4z',
    building: 'M3 21h18v-2H3v2zm2-4h3V3H5v14zm5 0h4V7h-4v10zm6 0h3V11h-3v6z',
    map: 'M15 5l-6 2-6-2v14l6 2 6-2 6 2V7l-6-2zm0 2.18 4 1.34v10.3l-4-1.34V7.18zM5 8.52l4 1.34v10.3l-4-1.34V8.52z',
    folder: 'M10 4l2 2h8a2 2 0 012 2v8a3 3 0 01-3 3H5a3 3 0 01-3-3V7a3 3 0 013-3h5z',
    stack: 'M12 2L1 7l11 5 9-4.09V17h2V7L12 2zm0 12L5.74 11.15 4 12l8 4 8-4-1.74-.85L12 14zm0 4l-6.26-2.85L4 16l8 4 8-4-1.74-.85L12 18z',
    download: 'M5 20h14v-2H5v2zM11 4v8H8l4 4 4-4h-3V4h-2z',
    eye: 'M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7zm0 11a4 4 0 110-8 4 4 0 010 8z',
    lock: 'M17 8h-1V6a4 4 0 10-8 0v2H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V10a2 2 0 00-2-2zm-6 0V6a2 2 0 114 0v2h-4z',
};

const baseChartOptions = () => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    plugins: {
        legend: {
            position: 'top',
            labels: {
                boxWidth: 10,
                usePointStyle: true,
                pointStyle: 'circle',
                color: '#334155',
            },
        },
        tooltip: {
            backgroundColor: '#0f172a',
            titleColor: '#f8fafc',
            bodyColor: '#f8fafc',
            borderColor: '#1e293b',
            borderWidth: 1,
        },
    },
    scales: {
        x: {
            ticks: { color: '#64748b' },
            grid: { color: chartPalette.grid },
        },
        y: {
            ticks: { color: '#64748b' },
            grid: { color: chartPalette.grid },
        },
    },
});

const buildNumberTick = () => ({
    callback: (value) => formatNumber(value),
});

const createChart = (canvasRef, config) => {
    if (!canvasRef.value) {
        return;
    }

    const ctx = canvasRef.value.getContext('2d');
    if (!ctx) {
        return;
    }

    const chart = new Chart(ctx, config);
    chartInstances.push(chart);
};

const destroyCharts = () => {
    chartInstances.forEach((chart) => chart.destroy());
    chartInstances.length = 0;
};

const mountTeacherCharts = () => {
    const schoolBarColors = schoolPerformance.value.map((_, index) => {
        return teacherChartBarPalette[index % teacherChartBarPalette.length];
    });

    const schoolBorderColors = schoolPerformance.value.map((_, index) => {
        return teacherChartBorderPalette[index % teacherChartBorderPalette.length];
    });

    createChart(topTeachersChartRef, {
        type: 'bar',
        data: {
            labels: schoolPerformance.value.map((school) => school.school),
            datasets: [
                {
                    label: 'Total Activity (Opens + Downloads)',
                    data: schoolPerformance.value.map((school) => school.totalActivity),
                    backgroundColor: schoolBarColors,
                    borderColor: schoolBorderColors,
                    borderWidth: 1.5,
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 18,
                },
            ],
        },
        options: {
            ...baseChartOptions(),
            indexAxis: 'y',
            interaction: {
                mode: 'nearest',
                axis: 'y',
                intersect: true,
            },
            plugins: {
                ...baseChartOptions().plugins,
                tooltip: {
                    ...baseChartOptions().plugins.tooltip,
                    position: 'nearest',
                    displayColors: false,
                    callbacks: {
                        title(items) {
                            const school = schoolPerformance.value[items[0]?.dataIndex ?? -1];
                            return school?.school ?? 'School';
                        },
                        label(context) {
                            const school = schoolPerformance.value[context.dataIndex];
                            return `Total Activity: ${formatNumber(school?.totalActivity ?? context.parsed.x ?? 0)}`;
                        },
                        afterBody(items) {
                            const school = schoolPerformance.value[items[0]?.dataIndex ?? -1];
                            if (!school) {
                                return [];
                            }

                            return [
                                `Opens: ${formatNumber(school.opens)}`,
                                `Downloads: ${formatNumber(school.downloads)}`,
                                `District: ${school.district}`,
                                `Teachers: ${formatNumber(school.totalTeachers)}`,
                            ];
                        },
                    },
                },
            },
            scales: {
                x: {
                    ...baseChartOptions().scales.x,
                    ticks: buildNumberTick(),
                },
                y: {
                    ...baseChartOptions().scales.y,
                    grid: { display: false },
                },
            },
        },
    });

    createChart(opensVsDownloadsChartRef, {
        type: 'doughnut',
        data: {
            labels: ['Opens', 'Downloads'],
            datasets: [
                {
                    label: 'Interaction Split',
                    data: [teacherOpensTotal.value, teacherDownloadsTotal.value],
                    backgroundColor: [chartPalette.blue, chartPalette.emerald],
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 4,
                },
            ],
        },
        options: {
            ...baseChartOptions(),
            scales: {},
            cutout: '64%',
        },
    });

    createChart(teacherTrendChartRef, {
        type: 'line',
        data: {
            labels: teacherTrendSeries.value.labels,
            datasets: [
                {
                    label: `${teacherTrendPeriodLabel.value} Opens`,
                    data: teacherTrendSeries.value.opens,
                    borderColor: chartPalette.blue,
                    backgroundColor: 'rgba(37, 99, 235, 0.12)',
                    borderWidth: 2,
                    tension: 0.35,
                    fill: true,
                    pointRadius: 2.5,
                    pointHoverRadius: 4,
                },
                {
                    label: `${teacherTrendPeriodLabel.value} Downloads`,
                    data: teacherTrendSeries.value.downloads,
                    borderColor: chartPalette.emerald,
                    backgroundColor: 'rgba(16, 185, 129, 0.06)',
                    borderWidth: 2,
                    tension: 0.35,
                    fill: true,
                    pointRadius: 2.5,
                    pointHoverRadius: 4,
                },
            ],
        },
        options: {
            ...baseChartOptions(),
            animation: buildProgressiveLineAnimation(teacherTrendSeries.value.labels.length),
            scales: {
                x: {
                    ...baseChartOptions().scales.x,
                    grid: { display: false },
                },
                y: {
                    ...baseChartOptions().scales.y,
                    ticks: buildNumberTick(),
                },
            },
        },
    });
};

const mountDistrictCharts = () => {
    createChart(districtHeatmapChartRef, {
        type: 'bar',
        data: {
            labels: districtHeatmap.value.map((row) => row.district),
            datasets: [
                {
                    label: 'District Activity Intensity',
                    data: districtHeatmap.value.map((row) => row.totalActivity),
                    backgroundColor: districtHeatmap.value.map((row) => `rgba(37, 99, 235, ${Math.max(0.2, row.intensity)})`),
                    borderColor: districtHeatmap.value.map((row) => `rgba(30, 64, 175, ${Math.max(0.35, row.intensity)})`),
                    borderWidth: 1.5,
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 22,
                },
            ],
        },
        options: {
            ...baseChartOptions(),
            indexAxis: 'y',
            interaction: {
                mode: 'nearest',
                axis: 'y',
                intersect: true,
            },
            plugins: {
                ...baseChartOptions().plugins,
                legend: { display: false },
                tooltip: {
                    ...baseChartOptions().plugins.tooltip,
                    displayColors: false,
                    callbacks: {
                        title(items) {
                            const district = districtHeatmap.value[items[0]?.dataIndex ?? -1];
                            return district?.district ?? 'District';
                        },
                        label(context) {
                            return `Total Activity: ${formatNumber(context.parsed.x ?? 0)}`;
                        },
                        afterBody(items) {
                            const district = districtHeatmap.value[items[0]?.dataIndex ?? -1];
                            if (!district) {
                                return [];
                            }

                            return [
                                `Teachers: ${formatNumber(district.totalTeachers)}`,
                                `Opens: ${formatNumber(district.opens)}`,
                                `Downloads: ${formatNumber(district.downloads)}`,
                            ];
                        },
                    },
                },
            },
            scales: {
                x: {
                    ...baseChartOptions().scales.x,
                    ticks: buildNumberTick(),
                },
                y: {
                    ...baseChartOptions().scales.y,
                    grid: { display: false },
                },
            },
        },
    });

    createChart(districtRadarChartRef, {
        type: 'line',
        data: {
            labels: districtRadarLineData.value.labels,
            datasets: districtRadarLineData.value.datasets,
        },
        options: {
            ...baseChartOptions(),
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                ...baseChartOptions().plugins,
                tooltip: {
                    ...baseChartOptions().plugins.tooltip,
                    callbacks: {
                        title(items) {
                            return `District: ${items[0]?.label ?? 'Unknown'}`;
                        },
                        label(context) {
                            const district = districtRadarLineData.value.districts[context.datasetIndex];
                            return `${district?.district ?? 'District'}: ${formatNumber(context.parsed?.y ?? 0)} total activity`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    ...baseChartOptions().scales.x,
                    title: {
                        display: true,
                        text: 'District Rank',
                    },
                },
                y: {
                    ...baseChartOptions().scales.y,
                    title: {
                        display: true,
                        text: 'Total Activity',
                    },
                    ticks: buildNumberTick(),
                },
            },
        },
    });
};

const mountResourceCharts = () => {
    const categoryColors = categoryPopularity.value.map((_, index) => {
        return categoryChartPalette[index % categoryChartPalette.length];
    });

    const categoryLabels = categoryPopularity.value.map((category) => category.category);
    const categoryTotals = categoryPopularity.value.map((category) => category.total);

    const showCategoryPlaceholder = categoryLabels.length > 0 && categoryUsageTotal.value === 0;

    createChart(resourceCategoriesChartRef, {
        type: 'doughnut',
        data: {
            labels: showCategoryPlaceholder ? ['No usage yet'] : categoryLabels,
            datasets: [
                {
                    label: 'Category Usage',
                    // Chart.js renders a blank doughnut when every value is 0.
                    // Show a single neutral slice until tracking activity exists.
                    data: showCategoryPlaceholder ? [1] : categoryTotals,
                    backgroundColor: showCategoryPlaceholder ? [chartPalette.slate] : categoryColors,
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 4,
                },
            ],
        },
        options: {
            ...baseChartOptions(),
            plugins: {
                ...baseChartOptions().plugins,
                tooltip: {
                    ...baseChartOptions().plugins.tooltip,
                    callbacks: {
                        label(context) {
                            if (showCategoryPlaceholder) {
                                return 'No opens/downloads recorded yet.';
                            }

                            return `${context.label}: ${formatNumber(context.parsed ?? 0)}`;
                        },
                    },
                },
            },
            scales: {},
            cutout: '62%',
        },
    });

    createChart(resourceUsageTrendChartRef, {
        type: 'line',
        data: {
            labels: monthlyResourceUsage.value.labels,
            datasets: [
                {
                    label: 'Monthly Opens',
                    data: monthlyResourceUsage.value.opens,
                    borderColor: chartPalette.blue,
                    backgroundColor: 'rgba(37, 99, 235, 0.12)',
                    borderWidth: 2,
                    tension: 0.32,
                    fill: true,
                    pointRadius: 2,
                    pointHoverRadius: 4,
                },
                {
                    label: 'Monthly Downloads',
                    data: monthlyResourceUsage.value.downloads,
                    borderColor: chartPalette.amber,
                    backgroundColor: 'rgba(245, 158, 11, 0.08)',
                    borderWidth: 2,
                    tension: 0.32,
                    fill: true,
                    pointRadius: 2,
                    pointHoverRadius: 4,
                },
            ],
        },
        options: {
            ...baseChartOptions(),
            scales: {
                x: {
                    ...baseChartOptions().scales.x,
                    grid: { display: false },
                },
                y: {
                    ...baseChartOptions().scales.y,
                    ticks: buildNumberTick(),
                },
            },
        },
    });
};

const mountMaterialCharts = () => {
    createChart(materialTeacherHoldingsChartRef, {
        type: 'bar',
        data: {
            labels: materialTeacherHoldings.value.map((row) => row.teacherName),
            datasets: [
                {
                    label: 'Total Quantity',
                    data: materialTeacherHoldings.value.map((row) => row.totalQuantity),
                    backgroundColor: chartPalette.blue,
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 18,
                },
            ],
        },
        options: {
            ...baseChartOptions(),
            indexAxis: 'y',
            scales: {
                x: {
                    ...baseChartOptions().scales.x,
                    ticks: buildNumberTick(),
                },
                y: {
                    ...baseChartOptions().scales.y,
                    grid: { display: false },
                },
            },
        },
    });

    createChart(materialSchoolHoldingsChartRef, {
        type: 'bar',
        data: {
            labels: materialSchoolHoldings.value.map((row) => row.school),
            datasets: [
                {
                    label: 'Total Quantity',
                    data: materialSchoolHoldings.value.map((row) => row.totalQuantity),
                    backgroundColor: chartPalette.emerald,
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 18,
                },
            ],
        },
        options: {
            ...baseChartOptions(),
            indexAxis: 'y',
            scales: {
                x: {
                    ...baseChartOptions().scales.x,
                    ticks: buildNumberTick(),
                },
                y: {
                    ...baseChartOptions().scales.y,
                    grid: { display: false },
                },
            },
        },
    });

    createChart(materialResourceTypeChartRef, {
        type: 'doughnut',
        data: {
            labels: materialResourceTypeBreakdown.value.map((row) => row.resourceType),
            datasets: [
                {
                    label: 'Quantity by Type',
                    data: materialResourceTypeBreakdown.value.map((row) => row.totalQuantity),
                    backgroundColor: [
                        chartPalette.blue,
                        chartPalette.emerald,
                        chartPalette.amber,
                        chartPalette.slate,
                        chartPalette.red,
                    ],
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 4,
                },
            ],
        },
        options: {
            ...baseChartOptions(),
            scales: {},
            cutout: '62%',
        },
    });

    createChart(materialLearningAreaChartRef, {
        type: 'bar',
        data: {
            labels: materialLearningAreaBreakdown.value.map((row) => row.learningArea),
            datasets: [
                {
                    label: 'Total Quantity',
                    data: materialLearningAreaBreakdown.value.map((row) => row.totalQuantity),
                    backgroundColor: chartPalette.amber,
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 26,
                },
            ],
        },
        options: {
            ...baseChartOptions(),
            scales: {
                x: {
                    ...baseChartOptions().scales.x,
                    ticks: {
                        color: '#64748b',
                        maxRotation: 40,
                        minRotation: 30,
                        callback: (value, index) => {
                            const label = materialLearningAreaBreakdown.value[index]?.learningArea ?? '';
                            return label.length > 16 ? `${label.slice(0, 16)}…` : label;
                        },
                    },
                },
                y: {
                    ...baseChartOptions().scales.y,
                    ticks: buildNumberTick(),
                },
            },
        },
    });

    createChart(materialTrendChartRef, {
        type: 'line',
        data: {
            labels: materialMonthlyTrend.value.labels,
            datasets: [
                {
                    label: 'Total Quantity',
                    data: materialMonthlyTrend.value.quantities,
                    borderColor: chartPalette.blue,
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    borderWidth: 2,
                    tension: 0.32,
                    fill: true,
                    pointRadius: 2,
                    pointHoverRadius: 4,
                },
                {
                    label: 'Submissions',
                    data: materialMonthlyTrend.value.submissions,
                    borderColor: chartPalette.emerald,
                    backgroundColor: 'rgba(16, 185, 129, 0.06)',
                    borderWidth: 2,
                    tension: 0.32,
                    fill: true,
                    pointRadius: 2,
                    pointHoverRadius: 4,
                },
            ],
        },
        options: {
            ...baseChartOptions(),
            scales: {
                x: {
                    ...baseChartOptions().scales.x,
                    grid: { display: false },
                },
                y: {
                    ...baseChartOptions().scales.y,
                    ticks: buildNumberTick(),
                },
            },
        },
    });
};

const mountAllCharts = () => {
    destroyCharts();
    mountTeacherCharts();
    mountDistrictCharts();
    mountResourceCharts();
    mountMaterialCharts();
};

onMounted(() => {
    Chart.defaults.responsive = true;
    Chart.defaults.maintainAspectRatio = false;
    Chart.defaults.interaction.mode = 'index';
    Chart.defaults.interaction.intersect = false;
    Chart.defaults.plugins.legend.position = 'top';
    Chart.defaults.font.family = 'Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial';

    mountAllCharts();
});

watch(
    () => [
        teacherActivityData.value,
        teacherOpensTotal.value,
        teacherDownloadsTotal.value,
        teacherTrendSeries.value,
        teacherTrendPeriod.value,
        districtPerformance.value,
        schoolPerformance.value,
        districtLeaderboardRows.value,
        districtHeatmap.value,
        districtRadarSelection.value,
        districtRadarLineData.value,
        categoryPopularity.value,
        monthlyResourceUsage.value,
        materialTeacherHoldings.value,
        materialSchoolHoldings.value,
        materialResourceTypeBreakdown.value,
        materialLearningAreaBreakdown.value,
        materialMonthlyTrend.value,
    ],
    () => {
        mountAllCharts();
    },
    { deep: true, flush: 'post' },
);

onBeforeUnmount(() => {
    destroyCharts();
});
</script>

<template>
    <Head title="Analytics" />

    <AdminLayout>
        <div class="mx-auto w-full max-w-[1500px] space-y-8 pb-8">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-slate-400">Learning Resource Analytics</p>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-12 xl:items-start">
                <aside class="space-y-4 xl:fixed xl:top-36 xl:left-[max(1rem,calc(50%-750px))] xl:w-[300px] 2xl:w-[330px]">
                    <form
                        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                        @submit.prevent="applyFilters"
                    >
                        <div class="space-y-3">
                            <div>
                                <label for="analytics_district" class="mb-1 block text-xs font-bold uppercase tracking-[0.12em] text-slate-500">District</label>
                                <select
                                    id="analytics_district"
                                    v-model="filterForm.district"
                                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                >
                                    <option value="">All districts</option>
                                    <option v-for="district in districtOptions" :key="district" :value="district">{{ district }}</option>
                                </select>
                            </div>

                            <div>
                                <label for="analytics_school" class="mb-1 block text-xs font-bold uppercase tracking-[0.12em] text-slate-500">School</label>
                                <select
                                    id="analytics_school"
                                    v-model="filterForm.school"
                                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100"
                                    :disabled="schoolOptions.length === 0"
                                >
                                    <option value="">All schools</option>
                                    <option v-for="school in schoolOptions" :key="school" :value="school">{{ school }}</option>
                                </select>
                            </div>

                            <div>
                                <label for="analytics_category" class="mb-1 block text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Resource Category</label>
                                <select
                                    id="analytics_category"
                                    v-model="filterForm.category"
                                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                >
                                    <option value="">All categories</option>
                                    <option v-for="category in categoryOptions" :key="category" :value="category">{{ category }}</option>
                                </select>
                            </div>

                            <div>
                                <label for="analytics_search" class="mb-1 block text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Search Name / Resource</label>
                                <input
                                    id="analytics_search"
                                    v-model="filterForm.search"
                                    type="text"
                                    placeholder="Teacher, school, district, file, material"
                                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 shadow-sm outline-none transition placeholder:font-normal placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                />
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-3 py-2 text-sm font-bold text-white transition hover:bg-blue-700"
                                >
                                    Apply
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="!hasActiveFilters"
                                    @click="clearFilters"
                                >
                                    Reset
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="grid grid-cols-2 gap-2 xl:grid-cols-3">
                        <article
                            v-for="card in kpiCards"
                            :key="card.key"
                            class="group aspect-square rounded-xl border border-slate-200 bg-white p-2 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md"
                            :class="['border-t-4', kpiToneClass(card.tone).accent]"
                        >
                            <div class="mb-1 flex items-center justify-between gap-1">
                                <span class="text-[10px] font-semibold leading-tight text-slate-500">{{ card.label }}</span>
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg" :class="kpiToneClass(card.tone).badge">
                                    <svg viewBox="0 0 24 24" class="h-3.5 w-3.5 fill-current" aria-hidden="true">
                                        <path :d="iconPaths[card.icon]" />
                                    </svg>
                                </span>
                            </div>

                            <p class="mt-auto text-xl font-black tracking-tight text-slate-900">
                                {{ formatNumber(card.value) }}
                            </p>
                        </article>
                    </div>
                </aside>

                <main class="space-y-8 xl:col-span-9 xl:col-start-4 xl:-mt-16">
                    <section class="space-y-4">

                        <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
                            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-9">
                                <div class="mb-3">
                                    <h3 class="text-sm font-black text-slate-900">Top 10 Most Active Schools</h3>
                                    <p class="text-xs font-medium text-slate-500">Activity is calculated from opens + downloads.</p>
                                </div>
                                <div class="h-80">
                                    <canvas ref="topTeachersChartRef" />
                                </div>
                            </article>

                            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-3">
                                <div class="mb-3">
                                    <h3 class="text-sm font-black text-slate-900">Opens vs Downloads</h3>
                                    <p class="text-xs font-medium text-slate-500">Overall teacher interaction mix.</p>
                                </div>
                                <div class="h-80">
                                    <canvas ref="opensVsDownloadsChartRef" />
                                </div>
                            </article>

                            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-12">
                                <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h3 class="text-sm font-black text-slate-900">Teacher Activity Trend</h3>
                                        <p class="text-xs font-medium text-slate-500">View opens and downloads by date range and period.</p>
                                    </div>
                                    <div class="grid w-full grid-cols-1 gap-2 sm:w-[430px] sm:grid-cols-3">
                                        <div>
                                            <label for="teacher_trend_from" class="mb-1 block text-[11px] font-black uppercase tracking-[0.16em] text-slate-500">
                                                From
                                            </label>
                                            <input
                                                id="teacher_trend_from"
                                                v-model="teacherTrendDateFrom"
                                                type="date"
                                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                            />
                                        </div>
                                        <div>
                                            <label for="teacher_trend_to" class="mb-1 block text-[11px] font-black uppercase tracking-[0.16em] text-slate-500">
                                                To
                                            </label>
                                            <input
                                                id="teacher_trend_to"
                                                v-model="teacherTrendDateTo"
                                                type="date"
                                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                            />
                                        </div>
                                        <div>
                                            <label for="teacher_trend_period" class="mb-1 block text-[11px] font-black uppercase tracking-[0.16em] text-slate-500">
                                                Period
                                            </label>
                                            <select
                                                id="teacher_trend_period"
                                                v-model="teacherTrendPeriod"
                                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                            >
                                                <option
                                                    v-for="option in teacherTrendPeriodOptions"
                                                    :key="option.value"
                                                    :value="option.value"
                                                >
                                                    {{ option.label }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="h-80">
                                    <canvas ref="teacherTrendChartRef" />
                                </div>
                            </article>
                        </div>
                    </section>

                    <section class="space-y-4">
                <div>
                    <h2 class="text-lg font-black text-slate-900">Resource Performance</h2>
                    <p class="text-sm font-medium text-slate-500">Track download/open behavior and category-level popularity.</p>
                </div>

                <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-4">
                        <div class="mb-3">
                            <h3 class="text-sm font-black text-slate-900">Popular Resource Categories</h3>
                            <p class="text-xs font-medium text-slate-500">Category share based on usage volume.</p>
                        </div>
                        <div class="h-80">
                            <canvas ref="resourceCategoriesChartRef" />
                        </div>
                        <p v-if="categoryPopularity.length > 0 && categoryUsageTotal === 0" class="mt-3 text-center text-xs font-medium text-slate-500">
                            No category usage recorded yet. Open or download a resource to populate this chart.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-8">
                        <div class="mb-3">
                            <h3 class="text-sm font-black text-slate-900">Monthly Resource Usage Trend</h3>
                            <p class="text-xs font-medium text-slate-500">Monthly opens and downloads across all resources.</p>
                        </div>
                        <div class="h-80">
                            <canvas ref="resourceUsageTrendChartRef" />
                        </div>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-6">
                        <div class="mb-3">
                            <h3 class="text-sm font-black text-slate-900">Resources Never Opened</h3>
                            <p class="text-xs font-medium text-slate-500">Content that may need visibility improvements.</p>
                        </div>

                        <div class="custom-scrollbar h-72 overflow-auto">
                            <table class="w-full min-w-[660px] text-xs">
                                <thead class="sticky top-0 bg-slate-50 text-left font-black uppercase tracking-[0.16em] text-slate-500">
                                    <tr>
                                        <th class="px-3 py-2">Resource</th>
                                        <th class="px-3 py-2">Category</th>
                                        <th class="px-3 py-2">Folder</th>
                                        <th class="px-3 py-2">Uploaded</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="resource in resourcesNeverOpened" :key="resource.title" class="border-t border-slate-100">
                                        <td class="px-3 py-2 font-semibold text-slate-800">{{ resource.title }}</td>
                                        <td class="px-3 py-2 text-slate-700">{{ resource.category }}</td>
                                        <td class="px-3 py-2 text-slate-700">{{ resource.folder }}</td>
                                        <td class="px-3 py-2 text-slate-700">{{ resource.uploadedAt }}</td>
                                    </tr>
                                    <tr v-if="resourcesNeverOpened.length === 0" class="border-t border-slate-100">
                                        <td colspan="4" class="px-3 py-6 text-center text-slate-500">No never-opened resources for the selected filter.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-6">
                        <div class="mb-3">
                            <h3 class="text-sm font-black text-slate-900">Locked Resources With Highest Activity</h3>
                            <p class="text-xs font-medium text-slate-500">High-demand resources that are currently locked.</p>
                        </div>

                        <div class="custom-scrollbar h-72 overflow-auto">
                            <table class="w-full min-w-[700px] text-xs">
                                <thead class="sticky top-0 bg-slate-50 text-left font-black uppercase tracking-[0.16em] text-slate-500">
                                    <tr>
                                        <th class="px-3 py-2">Resource</th>
                                        <th class="px-3 py-2">Category</th>
                                        <th class="px-3 py-2 text-right">Opens</th>
                                        <th class="px-3 py-2 text-right">Downloads</th>
                                        <th class="px-3 py-2 text-right">Total Activity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="resource in lockedResourcesWithActivity" :key="resource.title" class="border-t border-slate-100">
                                        <td class="px-3 py-2 font-semibold text-slate-800">{{ resource.title }}</td>
                                        <td class="px-3 py-2 text-slate-700">{{ resource.category }}</td>
                                        <td class="px-3 py-2 text-right text-slate-700">{{ formatNumber(resource.opens) }}</td>
                                        <td class="px-3 py-2 text-right text-slate-700">{{ formatNumber(resource.downloads) }}</td>
                                        <td class="px-3 py-2 text-right font-bold text-red-600">{{ formatNumber(resource.totalActivity) }}</td>
                                    </tr>
                                    <tr v-if="lockedResourcesWithActivity.length === 0" class="border-t border-slate-100">
                                        <td colspan="5" class="px-3 py-6 text-center text-slate-500">No locked high-activity resources for the selected filter.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </article>
                </div>
                    </section>

                </main>
            </div>

            <div
                v-if="props.loadError"
                class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800"
            >
                {{ props.loadError }}
            </div>
        </div>
    </AdminLayout>
</template>
