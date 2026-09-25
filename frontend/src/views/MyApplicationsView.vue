<script setup>


import { onMounted, reactive } from 'vue';
import { RouterLink } from 'vue-router';
import axios from 'axios';
import PulseLoader from 'vue-spinner/src/PulseLoader.vue';


const state = reactive({
    applications: [],
    isLoading: true,
});


onMounted(async () => {
    try {
        const response = await axios.get("/api/applications");

        state.applications = response.data.data;
    } catch (error) {
        console.error('Error fetching applications', error);
    } finally {
        state.isLoading = false;
    }

});

const statusClass = (status) => {
    if (status === 'accepted') {
        return 'bg-green-100 text-green-700'
    }

    if (status === 'rejected') {
        return 'bg-red-100 text-red-700'
    }

    return 'bg-amber-100 text-amber-700'
};


</script>


<template>
    <section class="mx-auto max-w-5xl px-6 py-10">
        <h1 class="text-3xl font-bold text-slate-900">
            My Applications
        </h1>

        <div v-if="state.isLoading" class="flex justify-center py-20">
            <PulseLoader color="#4F46E5" />
        </div>

        <div v-else-if="state.applications.length === 0"
            class="mt-8 rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
            <p class="text-slate-600">
                You haven't applied to any jobs yet.
            </p>
        </div>

        <div v-else class="mt-8 space-y-4">
            <RouterLink v-for="application in state.applications" :key="application.id"
                :to="`/jobs/${application.job.id}`"
                class="block rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-indigo-200 hover:shadow-md">
                <h2 class="text-xl font-bold text-slate-900">
                    {{ application.job.title }}
                </h2>

                <p class="mt-2 text-slate-500">
                    {{ application.job.location }}
                </p>

                <p class="mt-1 text-slate-500">
                    {{ application.job.type }}
                </p>

                <div class="mt-4">
                    <span :class="[
                        'inline-flex rounded-full px-3 py-1 text-sm font-semibold capitalize',
                        statusClass(application.status)
                    ]">
                        {{ application.status }}
                    </span>
                </div>




            </RouterLink>
        </div>
    </section>
</template>