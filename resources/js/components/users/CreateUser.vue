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
    first_name: '',
    last_name: '',
    name: '',
    email: '',
    password: '',
    status: 'active',
    roles: [] as string[],
});
const errors = ref<Record<string, string>>({});
const processing = ref(false);

const submit = async () => {
    processing.value = true;
    errors.value = {};
    try {
        const response = await InternalApi.store(form.value);
        emit('created', response);
        emit('update:open', false);
        form.value = {
            first_name: '',
            last_name: '',
            name: '',
            email: '',
            password: '',
            status: 'active',
            roles: [],
        }; // Reset
    } catch (e: any) {
        if (e.response && e.response.status === 422) {
            errors.value = e.response.data.errors;
            // Map array errors to strings for InputError
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
</script>

<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-[500px]">
      <DialogHeader>
        <DialogTitle>Create User</DialogTitle>
        <DialogDescription>
          Add a new user and assign roles.
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="submit" class="space-y-4 py-4 max-h-[70vh] overflow-y-auto px-1">
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
                <label for="first_name" class="text-sm font-medium leading-none">First Name</label>
                <input
                    id="first_name"
                    v-model="form.first_name"
                    type="text"
                    class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    autofocus
                />
                <InputError :message="errors.first_name" class="mt-1" />
            </div>
            
            <div class="space-y-2">
                <label for="last_name" class="text-sm font-medium leading-none">Last Name</label>
                <input
                    id="last_name"
                    v-model="form.last_name"
                    type="text"
                    class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                />
                <InputError :message="errors.last_name" class="mt-1" />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
                <label for="email" class="text-sm font-medium leading-none">Email Address *</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                />
                <InputError :message="errors.email" class="mt-1" />
            </div>
            
            <div class="space-y-2">
                <label for="password" class="text-sm font-medium leading-none">Password *</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    required
                    class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                />
                <InputError :message="errors.password" class="mt-1" />
            </div>
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
            <div class="grid grid-cols-2 gap-2 border rounded-md p-3 bg-muted/20">
                <label v-for="role in props.roles" :key="role.id" class="flex items-center space-x-2 cursor-pointer group">
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
                <div v-if="!props.roles || props.roles.length === 0" class="col-span-full text-sm text-muted-foreground italic">
                    No roles available.
                </div>
            </div>
            <InputError :message="errors.roles" class="mt-1" />
        </div>

        <DialogFooter class="pt-4">
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
                <span v-else>Create User</span>
            </button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
