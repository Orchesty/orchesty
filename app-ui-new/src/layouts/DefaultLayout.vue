<script setup lang="ts"></script>

<template>
  <div class="min-h-screen flex flex-col bg-gray-50">
    <header class="bg-white border-b border-gray-200 p-4 shadow-sm">
      <div class="container mx-auto">
        <h1 class="text-xl font-semibold text-gray-800">Vue.js Production Stack 2025</h1>
      </div>
    </header>

    <main class="flex-1 container mx-auto p-6">
      <slot />
    </main>

    <footer class="bg-white border-t border-gray-200 p-4 mt-auto">
      <div class="container mx-auto text-center text-sm text-gray-600">
        <p>&copy; 2025 - Modern Vue.js Application</p>
      </div>
    </footer>
  </div>
</template>

