<script setup lang="ts">
import Card from '@/components/ui/Card.vue'
</script>

<template>
  <main class="bg-gray-50 dark:bg-gray-900">
    <section class="bg-gray-50 dark:bg-gray-900">
      <div
        class="mx-auto flex h-screen max-w-screen-xl items-center justify-center px-4 py-8 lg:gap-20 lg:py-16"
      >
        <div class="w-full place-self-center max-w-md mx-auto">
          <!-- Logo -->
          <div class="mb-6 text-center sm:mb-6">
            <div
              class="inline-flex items-center gap-3 text-center text-3xl font-semibold text-gray-900 dark:text-white"
            >
              <img src="/logo.svg" alt="Orchesty" class="h-9 w-9" />
              <span>Orchesty</span>
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

