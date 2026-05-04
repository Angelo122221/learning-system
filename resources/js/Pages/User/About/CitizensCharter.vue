<script setup>
import DepedSystemsStrip from '@/Components/DepedSystemsStrip.vue';
import PortalLinkSection from '@/Components/PortalLinkSection.vue';
import { depedSystemsLinks, officialLinks } from '@/data/portalLinks';
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head } from '@inertiajs/vue3';

const charterPdfHref = '/documents/DepEd-Citizens-Charter-2025.pdf';
const youtubeEmbedUrl = 'https://www.youtube.com/embed/Iohd_mOm0xw?start=1';
const relatedManuals = [
    {
        title: 'Reengineering Manual',
        href: 'https://arta.gov.ph/reengineering-manual/',
    },
    {
        title: 'Regulatory Impact Assessment Manual',
        href: 'https://arta.gov.ph/regulatory-impact-assessment-ria/',
    },
];
</script>

<template>
    <Head title="Citizen's Charter" />

    <UserLayout>
        <section class="mx-auto max-w-[1120px]">
            <div class="mb-3">
                <h1 class="text-3xl font-black tracking-tight text-[#081436] sm:text-4xl">
                    Citizen's Charter
                </h1>
            </div>

            <article class="rounded-xl border border-slate-200 bg-white px-5 py-5 shadow-[0_16px_40px_rgba(15,23,42,0.06)] sm:px-8">
                <div class="space-y-2 text-[13px] font-medium leading-5 text-slate-700 sm:text-sm sm:leading-6">
                    <p>
                        The Citizen's Charter sets the service standards and guides the delivery of government services in the Division of Ozamiz City.
                    </p>
                    <p>
                        It is anchored on Section 6 of the <strong class="font-black text-[#081436]">Republic Act No. 11032</strong>, otherwise known as the <strong class="font-black text-[#081436]">Ease of Doing Business and Efficient Government Service Delivery Act (EODB-EGSD)</strong>.
                    </p>
                </div>

                <div class="my-4 rounded-lg border border-orange-200 bg-[#fff7ef] px-4 py-4 sm:px-6">
                    <blockquote class="text-[13px] font-medium leading-5 text-slate-700 sm:text-sm sm:leading-6">
                        <span class="mr-1 align-baseline text-xl font-black leading-none text-orange-500" aria-hidden="true">"</span>It is hereby declared that the policy of the State to promote integrity, accountability, proper management of public affairs and public property as well as to establish effective practices, aimed at efficient turnaround of the delivery of government services and the prevention of graft and corruption in the government. Towards this end, the State shall maintain honesty and responsibility among its public officials and employees, and shall take appropriate measures to promote transparency in each agency with regard to the manner of transacting with the public, which shall encompass a program for the adoption of simplified requirements and procedures that will reduce red tape and expedite business and nonbusiness related transactions in government.<span class="ml-1 align-baseline text-xl font-black leading-none text-orange-500" aria-hidden="true">"</span>
                    </blockquote>
                </div>

                <div class="space-y-2 text-[13px] font-medium leading-5 text-slate-700 sm:text-sm sm:leading-6">
                    <p>
                        Section 6 of the Act stipulates that all government agencies shall set up their respective most current and updated service standards to be known as the Citizen's Charter in the form of information billboards, which shall be posted at the most conspicuous place, in their respective websites and in the form of published materials such as handbook written either in English or Filipino, or in local dialect.
                    </p>
                    <p>
                        In strict adherence with Section 6 of RA 11032, the Department of Education established the following service standards or DepEd Citizen's Charter:
                    </p>
                    <a
                        :href="charterPdfHref"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex w-fit font-black text-blue-700 underline decoration-blue-200 underline-offset-4 transition hover:text-blue-800 hover:decoration-blue-500 focus:outline-none focus-visible:ring-4 focus-visible:ring-blue-100"
                    >
                        2025 Division of Ozamiz City Citizen's Charter (1st Edition)
                    </a>
                </div>

                <div class="mt-4 flex justify-start">
                    <iframe
                        :src="youtubeEmbedUrl"
                        title="Citizen's Charter video"
                        class="aspect-video w-full max-w-[40rem] rounded-lg border border-slate-200 bg-black shadow-[0_12px_26px_rgba(15,23,42,0.10)]"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen
                    />
                </div>
            </article>

            <section class="mt-3 rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-[0_12px_32px_rgba(15,23,42,0.05)] sm:px-8">
                <h2 class="text-sm font-black text-[#081436]">Implementation Guides</h2>

                <p class="mt-3 text-[13px] font-medium leading-5 text-slate-700 sm:text-sm sm:leading-6">
                    The following manuals developed by the Anti-Red Tape Authority may also be used to guide the implementation of RA 11032 with the Department:
                </p>

                <ul class="mt-3 list-disc space-y-2 pl-5 text-[13px] font-medium leading-5 text-slate-700 sm:text-sm sm:leading-6">
                    <li v-for="manual in relatedManuals" :key="manual.href">
                        <a
                            :href="manual.href"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="font-black text-blue-700 underline decoration-blue-200 underline-offset-4 transition hover:text-blue-800 hover:decoration-blue-500 focus:outline-none focus-visible:ring-4 focus-visible:ring-blue-100"
                        >
                            {{ manual.title }}
                        </a>
                    </li>
                </ul>
            </section>

        </section>

        <section class="mx-auto mt-3 max-w-[1120px] -mb-4 md:-mb-6" aria-labelledby="portal-links-heading">
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
    </UserLayout>
</template>
