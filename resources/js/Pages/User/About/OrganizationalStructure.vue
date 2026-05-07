<script setup>
import DepedSystemsStrip from '@/Components/DepedSystemsStrip.vue';
import PortalLinkSection from '@/Components/PortalLinkSection.vue';
import { depedSystemsLinks, officialLinks } from '@/data/portalLinks';
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head } from '@inertiajs/vue3';

const leadershipChain = [
    {
        name: 'Dionesio L. Liwagon Jr., CESO VI',
        role: 'Assistant Schools Division Superintendent',
        photo: null,
    },
    {
        name: 'May P. Edullantes',
        role: 'Chief Education Supervisor',
        photo: null,
    },
    {
        name: 'Quirino D. Lonsagon',
        role: 'Education Program Supervisor',
        photo: null,
    },
];

const supportTeam = [
    {
        name: 'Mary Ann Grace S. Manili',
        role: 'Librarian II',
        photo: null,
    },
    {
        name: '',
        role: 'Project Development',
        photo: null,
    },
];
</script>

<template>
    <Head title="Organizational Structure" />

    <UserLayout>
        <section class="mx-auto max-w-[980px]">
            <div class="mb-5">
                <h1 class="text-3xl font-black tracking-tight text-[#081436] sm:text-4xl">
                    Organizational Structure
                </h1>

            </div>

            <section class="mx-auto max-w-[760px]" aria-label="Organizational reporting chart">
                <div class="flex flex-col items-center">
                    <template v-for="(member, index) in leadershipChain" :key="member.role">
                        <article class="org-card w-full max-w-[25rem]">
                            <div class="org-photo" aria-hidden="true">
                                <img
                                    v-if="member.photo"
                                    :src="member.photo"
                                    :alt="member.name"
                                    class="h-full w-full rounded-[0.65rem] object-cover"
                                >
                                <div v-else class="org-photo-placeholder">
                                    Photo
                                </div>
                            </div>

                            <div class="min-w-0">
                                <h2
                                    v-if="member.name"
                                    class="org-name"
                                >
                                    {{ member.name }}
                                </h2>
                                <p :class="['text-sm font-semibold leading-5 text-blue-700', member.name ? 'mt-1' : '']">
                                    {{ member.role }}
                                </p>
                            </div>
                        </article>

                        <div v-if="index < leadershipChain.length - 1" class="org-connector-vertical" />
                    </template>

                    <div class="org-branch">
                        <span class="org-branch-stem" aria-hidden="true" />
                        <span class="org-branch-line" aria-hidden="true" />
                    </div>

                    <div class="grid w-full gap-4 sm:grid-cols-2 sm:gap-6">
                        <article
                            v-for="member in supportTeam"
                            :key="member.role"
                            class="org-card"
                        >
                            <div class="org-photo" aria-hidden="true">
                                <img
                                    v-if="member.photo"
                                    :src="member.photo"
                                    :alt="member.name"
                                    class="h-full w-full rounded-[0.65rem] object-cover"
                                >
                                <div v-else class="org-photo-placeholder">
                                    Photo
                                </div>
                            </div>

                            <div class="min-w-0">
                                <h2
                                    v-if="member.name"
                                    class="org-name"
                                >
                                    {{ member.name }}
                                </h2>
                                <p :class="['text-sm font-semibold leading-5 text-blue-700', member.name ? 'mt-1' : '']">
                                    {{ member.role }}
                                </p>
                            </div>
                        </article>
                    </div>
                </div>
            </section>
        </section>

        <section class="mx-auto mt-8 max-w-[1120px] -mb-4 md:mt-10 md:-mb-6" aria-labelledby="portal-links-heading">
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

<style scoped>
.org-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    border: 1px solid rgb(226 232 240);
    border-radius: 0.75rem;
    background: #ffffff;
    padding: 0.85rem 1rem;
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
}

.org-photo {
    display: flex;
    width: 4.5rem;
    height: 4.5rem;
    flex: 0 0 auto;
    align-items: center;
    justify-content: center;
    border-radius: 0.85rem;
    background: #ffffff;
    box-shadow: inset 0 0 0 1px rgb(226 232 240);
    padding: 0.35rem;
}

.org-photo-placeholder {
    display: flex;
    width: 100%;
    height: 100%;
    align-items: center;
    justify-content: center;
    border-radius: 0.65rem;
    background: #eef4fc;
    color: #8da0bd;
    font-size: 0.65rem;
    font-weight: 900;
    letter-spacing: 0.16em;
    text-transform: uppercase;
}

.org-name {
    color: #081436;
    font-size: clamp(0.95rem, 1.7vw, 1.2rem);
    font-weight: 900;
    line-height: 1.15;
    white-space: nowrap;
}

.org-connector-vertical {
    width: 1px;
    height: 2rem;
    background: #b8cdf2;
}

.org-branch {
    position: relative;
    width: min(100%, 30rem);
    height: 2.35rem;
}

.org-branch-stem {
    position: absolute;
    left: 50%;
    top: 0;
    width: 1px;
    height: 1.15rem;
    background: #b8cdf2;
    transform: translateX(-50%);
}

.org-branch-line {
    position: absolute;
    left: 12%;
    right: 12%;
    top: 1.15rem;
    height: 1px;
    border-top: 1px solid #b8cdf2;
}

.org-branch::before,
.org-branch::after {
    position: absolute;
    top: 1.15rem;
    width: 1px;
    height: 1.2rem;
    background: #b8cdf2;
    content: '';
}

.org-branch::before {
    left: 12%;
}

.org-branch::after {
    right: 12%;
}

@media (max-width: 639px) {
    .org-card {
        gap: 0.8rem;
        padding: 0.8rem;
    }

    .org-photo {
        width: 4rem;
        height: 4rem;
    }

    .org-branch {
        width: 1px;
        height: 1.8rem;
        background: #b8cdf2;
    }

    .org-branch-stem,
    .org-branch-line,
    .org-branch::before,
    .org-branch::after {
        display: none;
    }
}
</style>
