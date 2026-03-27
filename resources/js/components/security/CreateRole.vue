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
import axios from 'axios';

type Permission = { id: number; name: string; action: string };

const props = defineProps<{
    permissions: Record<string, Permission[]>;
    open: boolean;
}>();

const emit = defineEmits(['update:open', 'created']);

const form = ref({
    name: '',
    permissions: [] as string[],
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

const submit = async () => {
    processing.value = true;
    errors.value = {};
    try {
        const response = await axios.post('/internal-api/roles', form.value);
        emit('created', response.data.role);
        emit('update:open', false);
        form.value = { name: '', permissions: [] };
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

const toggleGroup = (group: string) => {
    const groupPerms = (props.permissions[group] || []).map(p => p.name);
    const allSelected = groupPerms.every(p => form.value.permissions.includes(p));
    if (allSelected) {
        form.value.permissions = form.value.permissions.filter(p => !groupPerms.includes(p));
    } else {
        const toAdd = groupPerms.filter(p => !form.value.permissions.includes(p));
        form.value.permissions.push(...toAdd);
    }
};

const isGroupSelected = (group: string) => {
    const groupPerms = (props.permissions[group] || []).map(p => p.name);
    return groupPerms.length > 0 && groupPerms.every(p => form.value.permissions.includes(p));
};

const isGroupIndeterminate = (group: string) => {
    const groupPerms = (props.permissions[group] || []).map(p => p.name);
    const selected = groupPerms.filter(p => form.value.permissions.includes(p));
    return selected.length > 0 && selected.length < groupPerms.length;
};
</script>

<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-[560px]">
      <DialogHeader>
        <DialogTitle>Create Role</DialogTitle>
        <DialogDescription>Add a new role and assign permissions.</DialogDescription>
      </DialogHeader>

      <form @submit.prevent="submit" class="space-y-4 py-4">
        <div class="space-y-2">
            <label for="name" class="text-sm font-medium leading-none">Role Name</label>
            <input
                id="name"
                v-model="form.name"
                type="text"
                class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                placeholder="e.g. Editor"
                autofocus
            />
            <InputError :message="errors.name" class="mt-1" />
        </div>

        <div class="space-y-2">
            <label class="text-sm font-medium leading-none">Assign Permissions</label>
            <div class="border rounded-md bg-muted/20 max-h-72 overflow-y-auto divide-y divide-border">
                <div v-for="(perms, group) in permissions" :key="group" class="p-3">
                    <!-- Group header with select-all checkbox -->
                    <label class="flex items-center gap-2 cursor-pointer mb-2">
                        <input
                            type="checkbox"
                            :checked="isGroupSelected(group)"
                            :indeterminate="isGroupIndeterminate(group)"
                            @change="toggleGroup(group)"
                            class="h-4 w-4 rounded-sm border border-primary"
                        />
                        <span class="text-xs font-bold uppercase tracking-wider text-foreground/70">{{ group }}</span>
                    </label>
                    <!-- Permission checkboxes -->
                    <div class="grid grid-cols-3 gap-1.5 pl-6">
                        <label v-for="permission in perms" :key="permission.id" class="flex items-center gap-1.5 cursor-pointer group">
                            <input
                                type="checkbox"
                                :value="permission.name"
                                v-model="form.permissions"
                                class="h-3.5 w-3.5 rounded-sm border border-primary"
                            />
                            <span class="text-xs font-medium capitalize text-foreground/80 group-hover:text-primary transition-colors">
                                {{ permission.action }}
                            </span>
                        </label>
                    </div>
                </div>
                <div v-if="Object.keys(permissions).length === 0" class="p-3 text-sm text-muted-foreground italic">
                    No permissions available.
                </div>
            </div>
            <InputError :message="errors.permissions" class="mt-1" />
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
                <span v-else>Create Role</span>
            </button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
