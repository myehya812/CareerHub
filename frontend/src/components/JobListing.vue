<script setup>
import { RouterLink } from 'vue-router';
import { defineProps, ref, computed } from 'vue';

const props = defineProps({
  job: Object,
});

const showFullDescription = ref(false);

const toggleFullDescription = () => {
  showFullDescription.value = !showFullDescription.value;
};

const truncatedDescription = computed(() => {
  let description = props.job.description;
  if (!showFullDescription.value) {
    description = description.substring(0, 90) + '...';
  }
  return description;
});


const formattedSalary = computed(() => {
  const salary = props.job.salary;

  if (salary?.min == null || salary?.max == null) {
    return 'Salary not specified';
  }

  const min = Number(salary.min);
  const max = Number(salary.max);

  
  if (Number.isNaN(min) || Number.isNaN(max)) {
    return 'Salary not specified';
  }

  const formatNumber = (value) => {
    return new Intl.NumberFormat('en-US').format(value);
  };

  
  return `${salary.currency} ${formatNumber(min)} - ${formatNumber(max)}`;
});


</script>

<template>
  <article
    class="group flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-lg"
  >

    <div class="mb-4">
      <span
        class="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700"
      >
        {{ job.type }}
      </span>
    </div>

    <h3
      class="text-xl font-bold text-slate-900 transition group-hover:text-indigo-600"
    >
      {{ job.title }}
    </h3>

    <div class="mt-4 flex-grow">
      <p class="leading-6 text-slate-600">
        {{ truncatedDescription }}
      </p>

      <button
        @click="toggleFullDescription"
        class="mt-2 text-sm font-semibold text-indigo-600 hover:text-indigo-700"
      >
        {{ showFullDescription ? 'Show less' : 'Read more' }}
      </button>
    </div>

    <div class="mt-6">
      <p class="font-semibold text-slate-900">
        {{ formattedSalary }}
      </p>

      <p class="text-sm text-slate-500">
        per year
      </p>
    </div>

    <div class="my-5 border-t border-slate-100"></div>

    <div
      class="flex items-center justify-between gap-4"
    >
      <div class="flex items-center gap-2 text-sm text-slate-500">
        <i class="pi pi-map-marker text-indigo-500"></i>

        <span>
          {{ job.location }}
        </span>
      </div>

      <RouterLink
        :to="'/jobs/' + job.id"
        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700"
      >
        View Job
      </RouterLink>
    </div>

  </article>
</template>