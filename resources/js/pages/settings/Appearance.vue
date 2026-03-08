<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/appearance';
import type { BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Appearance settings',
        href: edit(),
    },
];

const settings = ref({
    company_name: '',
    primary_color: '#000000',
    secondary_color: '#4B5563',
    button_color: '#3B82F6',
    company_logo: '',
    company_icon: ''
});

const isSaving = ref(false);
const message = ref('');

const fetchSettings = async () => {
    try {
        const response = await axios.get('/internal-api/settings');
        settings.value = { ...settings.value, ...response.data };
        applyThemeColors();
    } catch (e) {
        console.error('Failed to load settings', e);
    }
};

const saveSettings = async () => {
    isSaving.value = true;
    message.value = '';
    try {
        await axios.post('/internal-api/settings', { settings: settings.value });
        message.value = 'Settings saved successfully!';
        applyThemeColors();
        setTimeout(() => message.value = '', 3000);
    } catch (e) {
        message.value = 'Failed to save settings.';
    } finally {
        isSaving.value = false;
    }
};

const handleFileUpload = async (event: Event, key: string) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('key', key);
    formData.append('image', file);

    try {
        const response = await axios.post('/internal-api/settings/upload', formData);
        (settings.value as any)[key] = response.data.url;
        message.value = 'Image uploaded successfully!';
        setTimeout(() => message.value = '', 3000);
    } catch (e) {
        console.error('Upload failed', e);
    }
};

const applyThemeColors = () => {
    document.documentElement.style.setProperty('--primary-color', settings.value.primary_color);
    document.documentElement.style.setProperty('--secondary-color', settings.value.secondary_color);
    document.documentElement.style.setProperty('--button-color', settings.value.button_color);
};

onMounted(() => {
    fetchSettings();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Appearance & Company Settings" />

        <h1 class="sr-only">Appearance & Company Settings</h1>

        <SettingsLayout>
            <div class="space-y-10">
                <section>
                    <Heading
                        variant="small"
                        title="Company Details"
                        description="Update your company name, logo, and icon"
                    />
                    <div class="mt-4 grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Company Name</label>
                            <input
                                v-model="settings.company_name"
                                type="text"
                                class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                placeholder="My Awesome Company"
                            />
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium block mb-2">Company Logo</label>
                                <div class="flex items-center gap-4">
                                    <div v-if="settings.company_logo" class="h-12 w-12 rounded border bg-muted flex items-center justify-center overflow-hidden">
                                        <img :src="settings.company_logo" alt="Logo" class="max-h-full max-w-full object-contain" />
                                    </div>
                                    <input type="file" accept="image/*" @change="(e) => handleFileUpload(e, 'company_logo')" class="text-sm" />
                                </div>
                            </div>

                            <div>
                                <label class="text-sm font-medium block mb-2">Company Icon</label>
                                <div class="flex items-center gap-4">
                                    <div v-if="settings.company_icon" class="h-10 w-10 rounded border bg-muted flex items-center justify-center overflow-hidden">
                                        <img :src="settings.company_icon" alt="Icon" class="max-h-full max-w-full object-contain" />
                                    </div>
                                    <input type="file" accept="image/*" @change="(e) => handleFileUpload(e, 'company_icon')" class="text-sm" />
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="border-t border-border"></div>

                <section>
                    <Heading
                        variant="small"
                        title="Theme Settings"
                        description="Customize the appearance mode and base colors"
                    />
                    <div class="mt-6 flex flex-col gap-6">
                        <div class="space-y-3">
                            <label class="text-sm font-medium">Appearance Mode</label>
                            <AppearanceTabs />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                            <div class="space-y-2">
                                <label class="text-sm font-medium block">Primary Color</label>
                                <div class="flex gap-2 items-center">
                                    <input type="color" v-model="settings.primary_color" class="h-10 w-12 rounded cursor-pointer" />
                                    <input type="text" v-model="settings.primary_color" class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm uppercase font-mono" />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium block">Secondary Color</label>
                                <div class="flex gap-2 items-center">
                                    <input type="color" v-model="settings.secondary_color" class="h-10 w-12 rounded cursor-pointer" />
                                    <input type="text" v-model="settings.secondary_color" class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm uppercase font-mono" />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium block">Button Color</label>
                                <div class="flex gap-2 items-center">
                                    <input type="color" v-model="settings.button_color" class="h-10 w-12 rounded cursor-pointer" />
                                    <input type="text" v-model="settings.button_color" class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm uppercase font-mono" />
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="flex items-center gap-4 pt-4">
                    <button
                        @click="saveSettings"
                        :disabled="isSaving"
                        class="inline-flex items-center justify-center rounded-md text-sm font-medium bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-8 py-2"
                        :style="`background-color: var(--button-color, ${settings.button_color})`"
                    >
                        {{ isSaving ? 'Saving...' : 'Save Settings' }}
                    </button>

                    <p v-if="message" class="text-sm text-green-600 dark:text-green-400 font-medium transition-all">
                        {{ message }}
                    </p>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
