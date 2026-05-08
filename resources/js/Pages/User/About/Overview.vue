<script setup>
import DepedSystemsStrip from '@/Components/DepedSystemsStrip.vue';
import PortalLinkSection from '@/Components/PortalLinkSection.vue';
import { depedSystemsLinks, officialLinks } from '@/data/portalLinks';
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head } from '@inertiajs/vue3';

const overviewParagraphs = [
    'CRYSTAL, which stands for Complete Resources for Year-Round Systematized Teaching and Learning, is a web-based learning resource portal designed to provide teachers with organized access to instructional materials and teacher-related services.',
    'Through the portal, teachers can browse resource categories, open folders, preview and download files, view featured learning materials, watch featured videos, and stay updated through official announcements. The system also provides access to teacher-specific features such as Teacher Profile and Materials Inventory.',
    'The CRYSTAL Portal supports year-round access to teaching and learning resources by presenting materials in a structured, accessible, and user-friendly platform. This guide is intended to help teachers understand the main features of the portal and use them effectively.',
];

const teacherCapabilities = [
    'Browse organized resource categories and folder collections.',
    'Preview and download learning files from the portal.',
    'Access featured learning materials and featured videos.',
    'Stay updated through official announcements and notices.',
    'Use Teacher Profile and Materials Inventory tools.',
];
</script>

<template>
    <Head title="Overview" />

    <UserLayout>
        <section class="mx-auto max-w-[1120px]">
            <div class="mb-3">
                <h1 class="text-3xl font-black tracking-tight text-[#081436] sm:text-4xl">
                    Overview
                </h1>
            </div>

            <article class="rounded-xl border border-slate-200 bg-white px-5 py-5 shadow-[0_16px_40px_rgba(15,23,42,0.06)] sm:px-8">
                <div class="space-y-3 text-[13px] font-medium leading-5 text-slate-700 sm:text-sm sm:leading-6">
                    <p v-for="paragraph in overviewParagraphs" :key="paragraph">
                        {{ paragraph }}
                    </p>
                </div>

                <div class="mt-5 border-t border-slate-200 pt-4">
                    <h2 class="text-sm font-black text-[#081436]">What Teachers Can Do in CRYSTAL</h2>

                    <ul class="mt-3 list-disc space-y-2 pl-5 text-[13px] font-medium leading-5 text-slate-700 sm:text-sm sm:leading-6">
                        <li v-for="item in teacherCapabilities" :key="item">
                            {{ item }}
                        </li>
                    </ul>
                </div>
            </article>
        </section>

        <section class="mx-auto mt-3 max-w-[1120px] -mb-4 md:-mb-6" aria-labelledby="portal-links-heading">
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
    </UserLayout>
</template>
