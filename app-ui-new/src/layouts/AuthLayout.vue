<script setup lang="ts">
import Card from '@/components/ui/Card.vue'
</script>

<template>
  <main class="bg-gray-50 dark:bg-gray-900">
    <section class="bg-gray-50 dark:bg-gray-900">
      <div
        class="mx-auto grid h-screen max-w-screen-xl px-4 py-8 lg:grid-cols-12 lg:gap-20 lg:py-16"
      >
        <div class="w-full place-self-center lg:col-span-6 lg:col-start-4">
          <!-- Logo -->
          <div class="mb-6 text-center sm:mb-6">
            <div
              class="inline-flex items-center text-center text-xl font-semibold text-gray-900 dark:text-white"
            >
              <!-- TODO: Přidat Orchesty logo jako SVG -->
              <span>Orchesty Enterprise</span>
            </div>
          </div>

          <!-- Card with content -->
          <div class="mx-auto max-w-lg">
            <Card>
              <slot />
            </Card>
          </div>
        </div>
      </div>
    </section>
  </main>
</template>

