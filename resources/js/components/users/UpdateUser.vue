<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import InputError from '@/components/InputError.vue';

const props = defineProps<{
    open: boolean;
    user: { id: number; first_name: string; last_name: string; name: string; email: string; status: string; roles: Array<{ name: string }> } | null;
    roles: Array<{ id: number; name: string }>;
}>();

const emit = defineEmits(['update:open', 'updated']);

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    status: 'active',
    roles: [] as string[],
});

// Watch for the user populating to fill the form details
watch(() => props.user, (newUser) => {
    if (newUser) {
        form.first_name = newUser.first_name || '';
        form.last_name = newUser.last_name || '';
        form.email = newUser.email || '';
        form.password = ''; // Leave password blank on edit
        form.status = newUser.status || 'active';
        form.roles = (newUser.roles || []).map(r => r.name);
        form.clearErrors();
    }
}, { immediate: true });

// Import axios directly to avoid window.axios TS complaints
import axios from 'axios';

const submit = () => {
    if (!props.user) return;
    
    axios.put(`/internal-api/users/${props.user.id}`, form.data())
        .then(() => {
            emit('updated');
            emit('update:open', false);
        })
        .catch((error: any) => {
            if (error.response?.data?.errors) {
                // Map API errors back to Inertia-style form errors
                Object.keys(error.response.data.errors).forEach(key => {
                    form.setError(key as any, error.response.data.errors[key][0]);
                });
            }
        });
};
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle>Edit User</DialogTitle>
                <DialogDescription>
                    Update details for this system user.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4 py-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label for="first_name" class="text-sm font-medium leading-none">First Name</label>
                        <input
                            id="first_name"
                            v-model="form.first_name"
                            type="text"
                            class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        />
                        <InputError :message="form.errors.first_name" />
                    </div>
                    
                    <div class="space-y-2">
                        <label for="last_name" class="text-sm font-medium leading-none">Last Name</label>
                        <input
                            id="last_name"
                            v-model="form.last_name"
                            type="text"
                            class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        />
                        <InputError :message="form.errors.last_name" />
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="email" class="text-sm font-medium leading-none">Email Address *</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="space-y-2">
                    <label for="password" class="text-sm font-medium leading-none">New Password (Optional)</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <p class="text-[0.8rem] text-muted-foreground">Leave blank to keep existing password.</p>
                    <InputError :message="form.errors.password" />
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
                    <InputError :message="form.errors.status" />
                </div>

                <div class="space-y-3 pt-2">
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
                        <div v-if="roles.length === 0" class="col-span-2 text-sm text-muted-foreground italic">
                            No roles available.
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4">
                    <button
                        type="button"
                        @click="$emit('update:open', false)"
                        class="text-sm font-medium pl-2 pr-4 py-2 text-muted-foreground hover:underline"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center justify-center rounded-md text-sm font-medium bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
                    >
                        <span v-if="form.processing">Saving...</span>
                        <span v-else>Update User</span>
                    </button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
