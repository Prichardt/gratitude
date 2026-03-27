<script setup lang="ts">
import { ref } from 'vue';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter
} from '@/components/ui/dialog';
import InputError from '@/components/InputError.vue';
import InternalApi from './InternalApi';

const props = defineProps<{
    roles: Array<{ id: number; name: string }>;
    open: boolean;
}>();

const emit = defineEmits(['update:open', 'created']);

const form = ref({
    name: '',
    url: '',
    status: 'active',
    roles: [] as string[],
});
const errors = ref<Record<string, string>>({});
const processing = ref(false);
const createdToken = ref<string | null>(null);
const copied = ref(false);

const submit = async () => {
    processing.value = true;
    errors.value = {};
    try {
        const response = await InternalApi.store(form.value);
        createdToken.value = response.plainTextToken ?? null;
        emit('created', response);
    } catch (e: any) {
        if (e.response && e.response.status === 422) {
            errors.value = e.response.data.errors;
            for (const key in errors.value) {
                if (Array.isArray(errors.value[key])) {
                    errors.value[key] = errors.value[key][0];
                }
            }
        } else {
            console.error(e);
        }
    } finally {
        processing.value = false;
    }
};

const copyToken = async () => {
    if (!createdToken.value) return;
    await navigator.clipboard.writeText(createdToken.value);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
};

const closeModal = () => {
    createdToken.value = null;
    copied.value = false;
    form.value = { name: '', url: '', status: 'active', roles: [] };
    emit('update:open', false);
};
</script>

<template>
  <Dialog :open="open" @update:open="!createdToken && $emit('update:open', $event)">
    <DialogContent class="sm:max-w-[480px]">

      <!-- Token Reveal Step -->
      <template v-if="createdToken">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2 text-green-700 dark:text-green-400">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            Application Key Created
          </DialogTitle>
          <DialogDescription>
            Copy your API token now. <strong>It will not be shown again.</strong>
          </DialogDescription>
        </DialogHeader>

        <div class="py-4 space-y-4">
          <div class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950/30 p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-green-700 dark:text-green-400 mb-2">Your API Token</p>
            <div class="font-mono text-xs break-all bg-white dark:bg-black/30 border border-green-200 dark:border-green-700 rounded p-3 select-all text-foreground leading-relaxed">
              {{ createdToken }}
            </div>
          </div>

          <button
            type="button"
            @click="copyToken"
            class="w-full inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium border border-input bg-background hover:bg-muted h-10 px-4 transition-colors"
          >
            <svg v-if="!copied" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
            <svg v-else class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            {{ copied ? 'Copied!' : 'Copy to Clipboard' }}
          </button>
        </div>

        <DialogFooter>
          <button
            type="button"
            @click="closeModal"
            class="inline-flex items-center justify-center rounded-md text-sm font-medium bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-6"
          >
            Done
          </button>
        </DialogFooter>
      </template>

      <!-- Create Form Step -->
      <template v-else>
        <DialogHeader>
          <DialogTitle>Create Application Key</DialogTitle>
          <DialogDescription>
            Add a new application key and assign roles.
          </DialogDescription>
        </DialogHeader>

        <form @submit.prevent="submit" class="space-y-4 py-4">
          <div class="space-y-2">
              <label for="name" class="text-sm font-medium leading-none">Application Name</label>
              <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                  placeholder="e.g. Mobile App"
                  autofocus
              />
              <InputError :message="errors.name" class="mt-1" />
          </div>

          <div class="space-y-2">
              <label for="url" class="text-sm font-medium leading-none">Application URL (Optional)</label>
              <input
                  id="url"
                  v-model="form.url"
                  type="url"
                  class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                  placeholder="https://example.com"
              />
              <InputError :message="errors.url" class="mt-1" />
          </div>

          <div class="space-y-2">
              <label for="status" class="text-sm font-medium leading-none">Status</label>
              <select
                  id="status"
                  v-model="form.status"
                  class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
              >
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
              </select>
              <InputError :message="errors.status" class="mt-1" />
          </div>

          <div class="space-y-2">
              <label class="text-sm font-medium leading-none">Assign Roles</label>
              <div class="grid grid-cols-2 gap-2 border rounded-md p-3 bg-muted/20 max-h-40 overflow-y-auto">
                  <label v-for="role in roles" :key="role.id" class="flex items-center space-x-2 cursor-pointer group">
                      <input
                          type="checkbox"
                          :value="role.name"
                          v-model="form.roles"
                          class="peer h-4 w-4 shrink-0 rounded-sm border border-primary text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                      />
                      <span class="text-sm font-medium group-hover:text-primary transition-colors">
                          {{ role.name }}
                      </span>
                  </label>
                  <div v-if="roles.length === 0" class="col-span-full text-sm text-muted-foreground italic">
                      No roles available.
                  </div>
              </div>
              <InputError :message="errors.roles" class="mt-1" />
          </div>

          <DialogFooter>
              <button
                  type="button"
                  @click="$emit('update:open', false)"
                  class="text-sm font-medium text-muted-foreground hover:underline px-4 py-2"
              >
                  Cancel
              </button>
              <button
                  type="submit"
                  :disabled="processing"
                  class="inline-flex items-center justify-center rounded-md text-sm font-medium bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
              >
                  <span v-if="processing">Saving...</span>
                  <span v-else>Create Key</span>
              </button>
          </DialogFooter>
        </form>
      </template>

    </DialogContent>
  </Dialog>
</template>
