<script setup>
import Hero from '@/components/Hero.vue';
import HomeCards from '@/components/HomeCards.vue';
import JobListings from '@/components/JobListings.vue';
</script>

<template>
  <!-- Main CareerHub introduction -->
  <Hero />

  <!-- Separate sections for job seekers and employers -->
  <HomeCards />

  <!--
    Reuse JobListings but only show the first 3 jobs.

    limit="3"
      -> show only 3 jobs

    showButton="true"
      -> show the button that links to the full jobs page
  -->
  <JobListings :limit="3" :showButton="true" />
</template>