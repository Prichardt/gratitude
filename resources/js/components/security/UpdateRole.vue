<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
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
    open: boolean;
    role: { id: number; name: string; permissions: Array<{ name: string }> } | null;
    permissions: Record<string, Permission[]>;
}>();

const emit = defineEmits(['update:open', 'updated']);

const form = useForm({
    name: '',
    permissions: [] as string[],
});

watch(() => props.role, (newRole) => {
    if (newRole) {
        form.name = newRole.name || '';
        form.permissions = (newRole.permissions || []).map(p => p.name);
        form.clearErrors();
    }
}, { immediate: true });

const submit = () => {
    if (!props.role) return;
    axios.put(`/internal-api/roles/${props.role.id}`, form.data())
        .then(() => {
            emit('updated');
            emit('update:open', false);
        })
        .catch((error: any) => {
            if (error.response?.data?.errors) {
                Object.keys(error.response.data.errors).forEach(key => {
                    form.setError(key as any, error.response.data.errors[key][0]);
                });
            }
        });
};

const toggleGroup = (group: string) => {
    const groupPerms = (props.permissions[group] || []).map(p => p.name);
    const allSelected = groupPerms.every(p => form.permissions.includes(p));
    if (allSelected) {
        form.permissions = form.permissions.filter(p => !groupPerms.includes(p));
    } else {
        const toAdd = groupPerms.filter(p => !form.permissions.includes(p));
        form.permissions.push(...toAdd);
    }
};

const isGroupSelected = (group: string) => {
    const groupPerms = (props.permissions[group] || []).map(p => p.name);
    return groupPerms.length > 0 && groupPerms.every(p => form.permissions.includes(p));
};

const isGroupIndeterminate = (group: string) => {
    const groupPerms = (props.permissions[group] || []).map(p => p.name);
    const selected = groupPerms.filter(p => form.permissions.includes(p));
    return selected.length > 0 && selected.length < groupPerms.length;
};
</script>

<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-[560px]">
      <DialogHeader>
        <DialogTitle>Edit Role</DialogTitle>
        <DialogDescription>Update role name and permissions.</DialogDescription>
      </DialogHeader>

      <form @submit.prevent="submit" class="space-y-4 py-4">
        <div class="space-y-2">
            <label for="name" class="text-sm font-medium leading-none">Role Name</label>
            <input
                id="name"
                v-model="form.name"
                type="text"
                class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            />
            <InputError :message="form.errors.name" class="mt-1" />
        </div>

        <div class="space-y-2">
            <label class="text-sm font-medium leading-none">Assign Permissions</label>
            <div class="border rounded-md bg-muted/20 max-h-72 overflow-y-auto divide-y divide-border">
                <div v-for="(perms, group) in permissions" :key="group" class="p-3">
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
            <InputError :message="form.errors.permissions" class="mt-1" />
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
                :disabled="form.processing"
                class="inline-flex items-center justify-center rounded-md text-sm font-medium bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
            >
                <span v-if="form.processing">Saving...</span>
                <span v-else>Update Role</span>
            </button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
