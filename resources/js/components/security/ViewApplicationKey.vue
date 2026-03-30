<script setup lang="ts">
import { ref, watch } from 'vue';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter
} from '@/components/ui/dialog';
import InternalApi from './InternalApi';

const props = defineProps<{
    open: boolean;
    application_key: {
        id: number;
        name: string;
        url: string;
        status: string;
        token: string;
        roles: Array<{ name: string }>;
        created_at?: string;
        updated_at?: string;
    } | null;
}>();

const emit = defineEmits(['update:open', 'updated']);

const appKey = ref<typeof props.application_key>(null);
const regeneratedToken = ref<string | null>(null);
const copied = ref(false);
const regenerating = ref(false);
const toggling = ref(false);
const confirmRegenerate = ref(false);

watch(() => props.application_key, (val) => {
    appKey.value = val ? { ...val } : null;
    regeneratedToken.value = null;
    confirmRegenerate.value = false;
}, { immediate: true });

const copyToken = (token: string) => {
    const el = document.createElement('textarea');
    el.value = token;
    el.style.position = 'absolute';
    el.style.left = '-9999px';
    el.style.top = '0';
    el.setAttribute('readonly', '');
    document.body.appendChild(el);
    el.select();
    el.setSelectionRange(0, el.value.length);
    document.execCommand('copy');
    document.body.removeChild(el);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
};

const handleToggleStatus = async () => {
    if (!appKey.value) return;
    toggling.value = true;
    try {
        const response = await InternalApi.toggleStatus(appKey.value.id);
        appKey.value = response.data;
        emit('updated');
    } finally {
        toggling.value = false;
    }
};

const handleRegenerateToken = async () => {
    if (!appKey.value) return;
    regenerating.value = true;
    confirmRegenerate.value = false;
    try {
        const response = await InternalApi.regenerateToken(appKey.value.id);
        appKey.value = response.data;
        regeneratedToken.value = response.plainTextToken;
        emit('updated');
    } finally {
        regenerating.value = false;
    }
};

const maskedToken = (token: string) => {
    if (!token) return 'N/A';
    // Show first 8 chars and last 4, mask the middle
    const parts = token.split('|');
    if (parts.length === 2) {
        return parts[0] + '|' + parts[1].substring(0, 6) + '••••••••••••••••••••' + parts[1].slice(-4);
    }
    return token.substring(0, 8) + '••••••••••••••••••••' + token.slice(-4);
};

const formatDate = (dateStr?: string) => {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleString();
};
</script>

<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-[560px]">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
          </svg>
          Application Key Details
        </DialogTitle>
        <DialogDescription>
          View and manage this application key.
        </DialogDescription>
      </DialogHeader>

      <div v-if="appKey" class="py-4 space-y-4">

        <!-- Basic Info -->
        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1">Name</p>
            <p class="font-medium">{{ appKey.name }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1">Status</p>
            <span
              class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold"
              :class="appKey.status === 'active' ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200'"
            >
              {{ appKey.status }}
            </span>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1">URL</p>
            <p class="text-muted-foreground truncate">{{ appKey.url || 'N/A' }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1">Created</p>
            <p class="text-muted-foreground">{{ formatDate(appKey.created_at) }}</p>
          </div>
        </div>

        <!-- Roles -->
        <div>
          <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-2">Assigned Roles</p>
          <div class="flex flex-wrap gap-1">
            <span
              v-for="role in appKey.roles"
              :key="role.name"
              class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold bg-secondary text-secondary-foreground"
            >
              {{ role.name }}
            </span>
            <span v-if="!appKey.roles || appKey.roles.length === 0" class="text-muted-foreground italic text-xs">
              No roles assigned
            </span>
          </div>
        </div>

        <!-- Token section -->
        <div class="rounded-lg border border-border bg-muted/20 p-4 space-y-3">
          <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">API Token</p>

          <!-- Newly regenerated token -->
          <template v-if="regeneratedToken">
            <div class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950/30 p-3">
              <p class="text-xs text-green-700 dark:text-green-400 font-medium mb-2">New token generated — copy it now, it won't be shown again.</p>
              <div class="font-mono text-xs break-all bg-white dark:bg-black/30 border border-green-200 dark:border-green-700 rounded p-2 select-all leading-relaxed">
                {{ regeneratedToken }}
              </div>
            </div>
            <button
              type="button"
              @click="copyToken(regeneratedToken!)"
              class="w-full inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium border border-input bg-background hover:bg-muted h-9 px-4 transition-colors"
            >
              <svg v-if="!copied" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
              <svg v-else class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              {{ copied ? 'Copied!' : 'Copy Token' }}
            </button>
          </template>

          <!-- Masked existing token -->
          <template v-else>
            <div class="font-mono text-xs break-all bg-background border border-border rounded p-2 text-muted-foreground select-all">
              {{ maskedToken(appKey.token) }}
            </div>
            <p class="text-xs text-muted-foreground">The full token is not shown for security. Regenerate to get a new one.</p>
          </template>

          <!-- Regenerate confirmation -->
          <div v-if="confirmRegenerate && !regeneratedToken" class="rounded-md border border-amber-200 bg-amber-50 dark:bg-amber-950/30 p-3">
            <p class="text-xs text-amber-700 dark:text-amber-400 font-medium mb-2">
              This will revoke the existing token. Any application using it will lose access immediately.
            </p>
            <div class="flex gap-2">
              <button
                type="button"
                @click="handleRegenerateToken"
                :disabled="regenerating"
                class="inline-flex items-center justify-center rounded-md text-xs font-medium bg-amber-600 text-white hover:bg-amber-700 h-8 px-3 transition-colors disabled:opacity-50"
              >
                {{ regenerating ? 'Regenerating...' : 'Yes, regenerate' }}
              </button>
              <button
                type="button"
                @click="confirmRegenerate = false"
                class="inline-flex items-center justify-center rounded-md text-xs font-medium border border-input bg-background hover:bg-muted h-8 px-3 transition-colors"
              >
                Cancel
              </button>
            </div>
          </div>

          <button
            v-if="!confirmRegenerate && !regeneratedToken"
            type="button"
            @click="confirmRegenerate = true"
            class="inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium border border-input bg-background hover:bg-muted h-9 px-4 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Regenerate Token
          </button>
        </div>

      </div>

      <DialogFooter class="gap-2">
        <!-- Block / Unblock -->
        <button
          v-if="appKey"
          type="button"
          @click="handleToggleStatus"
          :disabled="toggling"
          class="inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium h-10 px-4 transition-colors disabled:opacity-50"
          :class="appKey.status === 'active'
            ? 'bg-red-600 text-white hover:bg-red-700'
            : 'bg-green-600 text-white hover:bg-green-700'"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path v-if="appKey.status === 'active'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {{ toggling ? 'Updating...' : (appKey.status === 'active' ? 'Block Key' : 'Unblock Key') }}
        </button>

        <button
          type="button"
          @click="$emit('update:open', false)"
          class="inline-flex items-center justify-center rounded-md text-sm font-medium bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-6"
        >
          Close
        </button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
