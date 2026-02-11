<script setup lang="ts">
import { useMouse } from '@vueuse/core'
import { Rocket, Mouse, Zap } from 'lucide-vue-next'
import DefaultLayout from '@/layouts/DefaultLayout.vue'

const { x, y } = useMouse()
</script>

<template>
  <DefaultLayout>
    <div class="max-w-4xl mx-auto space-y-8">
      <!-- Hero Section -->
      <div class="text-center space-y-4">
        <div class="flex justify-center">
          <Rocket :size="64" class="text-indigo-600" />
        </div>
        <h1 class="text-4xl font-bold text-indigo-600">
          Welcome to Vue.js Production Stack 2025
        </h1>
        <p class="text-xl text-gray-600">
          Modern, scalable, and production-ready architecture
        </p>
      </div>

      <!-- Feature Cards -->
      <div class="grid md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 hover:shadow-lg transition-shadow">
          <div class="flex items-center gap-3 mb-3">
            <Zap :size="24" class="text-yellow-500" />
            <h3 class="text-lg font-semibold text-gray-800">Tailwind CSS</h3>
          </div>
          <p class="text-gray-600">
            Utility-first CSS framework for rapid UI development
          </p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 hover:shadow-lg transition-shadow">
          <div class="flex items-center gap-3 mb-3">
            <Rocket :size="24" class="text-indigo-500" />
            <h3 class="text-lg font-semibold text-gray-800">VueUse</h3>
          </div>
          <p class="text-gray-600">
            Collection of essential Vue composition utilities
          </p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 hover:shadow-lg transition-shadow">
          <div class="flex items-center gap-3 mb-3">
            <Mouse :size="24" class="text-green-500" />
            <h3 class="text-lg font-semibold text-gray-800">TypeScript</h3>
          </div>
          <p class="text-gray-600">
            Type-safe development with modern tooling
          </p>
        </div>
      </div>

      <!-- VueUse Demo -->
      <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-lg p-8 border border-indigo-200">
        <div class="flex items-center gap-3 mb-4">
          <Mouse :size="28" class="text-indigo-600" />
          <h2 class="text-2xl font-bold text-gray-800">VueUse Demo: Mouse Position</h2>
        </div>
        <p class="text-gray-600 mb-4">
          Move your mouse around to see VueUse in action with the <code class="bg-white px-2 py-1 rounded text-sm">useMouse()</code> composable:
        </p>
        <div class="bg-white rounded-lg p-6 shadow-inner">
          <div class="grid grid-cols-2 gap-4 text-center">
            <div>
              <div class="text-sm text-gray-600 mb-1">X Position</div>
              <div class="text-3xl font-bold text-indigo-600">{{ x }}</div>
            </div>
            <div>
              <div class="text-sm text-gray-600 mb-1">Y Position</div>
              <div class="text-3xl font-bold text-purple-600">{{ y }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tech Stack -->
      <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Tech Stack Included</h2>
        <ul class="space-y-2">
          <li class="flex items-center gap-2 text-gray-700">
            <span class="text-green-500">✓</span>
            <span><strong>Vue 3</strong> with Composition API</span>
          </li>
          <li class="flex items-center gap-2 text-gray-700">
            <span class="text-green-500">✓</span>
            <span><strong>TypeScript</strong> for type safety</span>
          </li>
          <li class="flex items-center gap-2 text-gray-700">
            <span class="text-green-500">✓</span>
            <span><strong>Vite</strong> for lightning-fast builds</span>
          </li>
          <li class="flex items-center gap-2 text-gray-700">
            <span class="text-green-500">✓</span>
            <span><strong>Vue Router</strong> for navigation</span>
          </li>
          <li class="flex items-center gap-2 text-gray-700">
            <span class="text-green-500">✓</span>
            <span><strong>Pinia</strong> for state management</span>
          </li>
          <li class="flex items-center gap-2 text-gray-700">
            <span class="text-green-500">✓</span>
            <span><strong>Tailwind CSS</strong> for styling</span>
          </li>
          <li class="flex items-center gap-2 text-gray-700">
            <span class="text-green-500">✓</span>
            <span><strong>VueUse</strong> for composable utilities</span>
          </li>
          <li class="flex items-center gap-2 text-gray-700">
            <span class="text-green-500">✓</span>
            <span><strong>Axios</strong> for HTTP requests</span>
          </li>
          <li class="flex items-center gap-2 text-gray-700">
            <span class="text-green-500">✓</span>
            <span><strong>Lucide Vue Next</strong> for beautiful icons</span>
          </li>
          <li class="flex items-center gap-2 text-gray-700">
            <span class="text-green-500">✓</span>
            <span><strong>ESLint + Prettier</strong> for code quality</span>
          </li>
        </ul>
      </div>
    </div>
  </DefaultLayout>
</template>

