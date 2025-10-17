<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Separator } from '@/components/ui/separator';
import { usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { Check, CheckIcon, ChevronsUpDown, GripVertical, Search, X } from "lucide-vue-next"
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList,
    ComboboxTrigger
} from "@/components/ui/combobox"
import { cn } from "@/lib/utils"
import { debounce } from 'lodash-es'
import ComboboxGroup from '@/components/ui/combobox/ComboboxGroup.vue';
import NumberField from '@/components/ui/number-field/NumberField.vue';
import NumberFieldContent from '@/components/ui/number-field/NumberFieldContent.vue';
import NumberFieldInput from '@/components/ui/number-field/NumberFieldInput.vue';
import draggable from 'vuedraggable'
import { slugify as translitSlug } from 'transliteration'
import { Textarea } from '@/components/ui/textarea'

interface VendorOption {
    value: string;
    label: string;
}

interface FeatureOption {
    value: string;
    name: string;
    icon: string;
}

const vendors = ref<VendorOption[]>([])
const features = ref<FeatureOption[]>([])
const comboboxSearchTerm = ref('')
const comboboxFeatureSearchTerm = ref('')
const open = ref(false)

const selectedVendor = ref<VendorOption | undefined>()

const form = useForm({
    vendor: null as VendorOption | null,
    name: '',
    slug: '',
    description: '',
    qty: 0,
    type: '',
    size: 0,
    bed_size: '',
    view: '',
    occupancy: 1,
    free_breakfast: false,
    features: [] as FeatureOption[],
    standard_rate: 0,
})

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Units',
        href: '/units',
    },
    {
        title: 'Add Unit',
        href: '/units/create',
    },
];

const submit = () => {
    form.post(route('units.store'), {
        preserveScroll: false,
        onSuccess: () => {
            toast('Unit created', {
                description: 'The units has been created successfully',
                action: {
                    label: 'Close',
                },
            })
        },
        onError: () => {
            toast('Error creating unit', {
                description: 'Something went wrong, please try again',
				class: 'toast-destructive',
				action: { label: 'Close' }
            })
        },
    });
};

const handleKeydown = (e: KeyboardEvent) => {
    const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
    const isSaveShortcut = (isMac && e.metaKey && e.key === 's') || (!isMac && e.ctrlKey && e.key === 's');

    if (isSaveShortcut) {
        e.preventDefault(); // prevent browser's default "save" behavior
        submit();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});

const fetchComboboxData = debounce(() => {
    router.get(route('units.create'), {
        search: comboboxSearchTerm.value,
    }, {
        preserveScroll: true,
        preserveState: true,
        only: ['vendors'],
        onSuccess: () => {
            const newProps = usePage().props.vendors as VendorOption[] ?? []
            vendors.value = newProps
        }
    })
}, 300)

watch(comboboxSearchTerm, (term) => {
    if (!term) {
        vendors.value = []
        return
    }

    fetchComboboxData()
})

watch(selectedVendor, (newVal) => {
    form.vendor = newVal ?? null
})

const fetchFeatureOptions = debounce(() => {
    router.get(route('units.create'), {
        search: comboboxFeatureSearchTerm.value,
    }, {
        preserveScroll: true,
        preserveState: true,
        only: ['features'],
        onSuccess: () => {
            const pageProps = usePage().props as { features?: FeatureOption[] }
            features.value = pageProps.features ?? []
        },
    })
}, 300)

watch(comboboxFeatureSearchTerm, (term) => {
    if (!term) {
        features.value = []
        return
    }

    fetchFeatureOptions()
})

const removeFeature = (feature: FeatureOption) => {
    form.features = form.features.filter(f => f.value !== feature.value)
}

/**
 * Auto generate slug based on name field, but still editable manually.
 * Only regenerates if slug is empty or still matches previous auto value.
 */
watch(
    () => form.name,
    (newVal, oldVal) => {
        const prevAuto = translitSlug(oldVal ?? '', {
            lowercase: true,
            separator: '-'
        })
        // jika name dihapus → kosongkan slug juga
        if (!newVal || newVal.trim() === '') {
            form.slug = ''
            return
        }
        const currentAuto = translitSlug(newVal, {
            lowercase: true,
            separator: '-'
        })
        // hanya update otomatis kalau slug masih kosong atau sama seperti slug auto sebelumnya
        if (!form.slug || form.slug === prevAuto) {
            form.slug = currentAuto
        }
    }
)
</script>

<template>
    <Head title="Add Unit" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex flex-col space-y-6 min-h-full">
                <HeadingSmall title="Add unit" description="Add a new unit to include it in your management system" />

                <Separator />
    
                <form @submit.prevent="submit" class="space-y-6 h-full flex flex-col">
                    <div class="grid gap-2">
                        <Label>Vendor</Label>
                        <div class="mt-1 flex items-center gap-2">
                            <Combobox v-model="selectedVendor" class="w-full">
                                <ComboboxAnchor as-child>
                                    <ComboboxTrigger as-child>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            class="justify-between w-full"
                                            :class="{ 'font-normal text-muted-foreground': !selectedVendor?.value }"
                                        >
                                            {{ selectedVendor?.label ?? 'Select a vendor to assign' }}
                                            <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                        </Button>
                                    </ComboboxTrigger>
                                </ComboboxAnchor>
    
                                <ComboboxList align="start" class="w-full min-w-[200px]">
                                    <div class="relative w-full max-w-sm items-center combobox-input-wrapper">
                                        <ComboboxInput
                                            v-model="comboboxSearchTerm"
                                            placeholder="Search vendor..."
                                        />
                                        <span class="absolute start-0 inset-y-0 flex items-center justify-center px-3">
                                            <Search class="size-4 text-muted-foreground" />
                                        </span>
                                    </div>
    
                                    <!-- <ComboboxEmpty>
                                        No vendors found.
                                    </ComboboxEmpty> -->
    
                                    <ComboboxGroup :class="vendors.length < 1 ? 'p-0 border-none' : 'border-t'">
                                        <ComboboxItem
                                            v-for="(vendor, index) in vendors"
                                            :key="vendor.value"
                                            :value="vendor"
                                        >
                                            {{ vendor.label }}
        
                                            <ComboboxItemIndicator>
                                                <Check :class="cn('ml-auto h-4 w-4')" />
                                            </ComboboxItemIndicator>
                                        </ComboboxItem>
                                    </ComboboxGroup>
                                </ComboboxList>
                            </Combobox>
                            <Button type="button" variant="outline" @click="selectedVendor = undefined">
                                Clear
                                <X class="mt-0.5"/>
                            </Button>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" class="mt-1 block w-full" v-model="form.name" autocomplete="name" placeholder="Unit name (e.g. Whole Villa, Deluxe Room)" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="slug">Slug</Label>
                        <Input
                            id="slug"
                            class="mt-1 block w-full"
                            v-model="form.slug"
                            autocomplete="slug"
                            placeholder="unit-slug (e.g. whole-villa, deluxe-room)"
                        />
                        <InputError :message="form.errors.slug" />
                    </div>

                    <!-- Description -->
					<div class="grid gap-2">
						<Label for="description">Description</Label>
						<Textarea
							id="description"
							class="mt-1 block w-full resize-none min-h-52"
							v-model="form.description"
							autocomplete="description"
							placeholder="Description for this unit"
						/>
						<InputError :message="form.errors.description" />
					</div>

                    <!-- Qty -->
					<div class="grid gap-2">
						<Label for="qty">Qty</Label>
						<NumberField 
                            id="qty" 
                            :default-value="0" 
                            :min="0" 
                            class="mt-1"
                            v-model="form.qty"
                        >
                            <NumberFieldContent>
                                <NumberFieldInput class="text-start px-3 text-sm" />
                            </NumberFieldContent>
                        </NumberField>
						<InputError :message="form.errors.qty" />
					</div>

                    <!-- Type -->
                    <div class="grid gap-2">
                        <Label for="type">Type</Label>
                        <Input
                            id="type"
                            class="mt-1 block w-full"
                            v-model="form.type"
                            autocomplete="type"
                            placeholder="Unit type (e.g. Room, Villa, Bungalow, Suite)"
                        />
                        <InputError :message="form.errors.type" />
                    </div>

                    <!-- Size -->
					<div class="grid gap-2">
						<Label for="size">Size</Label>
						<NumberField 
                            id="size" 
                            :default-value="0" 
                            :min="0" 
                            class="mt-1 rounded-md"
                            v-model="form.size"
                        >
                            <NumberFieldContent class="relative">
                                <NumberFieldInput class="text-start px-3 text-sm pr-12" />
                                <!-- <span class="absolute border inset-y-0 right-0 w-12 flex items-center justify-center text-sm text-muted-foreground select-none bg-muted">sqm</span> -->
                            </NumberFieldContent>
                        </NumberField>
						<InputError :message="form.errors.size" />
					</div>

                    <div class="grid gap-2">
                        <Label for="standardRate">Standard rate</Label>
                        <NumberField 
                            id="standardRate" 
                            :default-value="0" 
                            :min="0" 
                            class="mt-1"
                            :format-options="{
                                style: 'currency',
                                currency: 'IDR',
                                currencyDisplay: 'narrowSymbol',
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            }"
                            v-model="form.standard_rate"
                        >
                            <NumberFieldContent>
                                <NumberFieldInput class="text-start px-3 text-sm" />
                            </NumberFieldContent>
                        </NumberField>
                        <InputError :message="form.errors.standard_rate" />
                    </div>

                    <div class="grid">
                        <Label for="features">Features</Label>
                        <Combobox v-model="form.features" v-model:open="open" class="mt-3 combobox-tags-input-wrapper">
                            <ComboboxAnchor as-child class="rounded-none h-auto !p-0 !block !w-full">
                                <ComboboxInput 
                                    id="features"
                                    v-model="comboboxFeatureSearchTerm"
                                    placeholder="Type to search and add features"
                                />
                            </ComboboxAnchor>

                            <ComboboxList align="start" :class="features.length < 1 ? 'border-none' : ''" class="w-full min-w-[200px]">
                                <ComboboxGroup :class="features.length < 1 ? 'p-0' : ''">
                                    <ComboboxItem
                                        v-for="(feature, index) in features"
                                        :key="feature.value"
                                        :value="feature"
                                        @select.prevent="() => {
                                            const exists = form.features.some(u => u.value === feature.value)
                                            if (!exists) {
                                                form.features.push(feature)
                                            }
                                            comboboxFeatureSearchTerm = ''
                                            open = false
                                        }"
                                    >
                                        {{ feature.name }}
                                    </ComboboxItem>
                                </ComboboxGroup>
                            </ComboboxList>
                        </Combobox>

                        <div v-if="form.features.length > 0" class="mt-4 pt-4 border-t text-sm">
                            <div>
                                <div class="flex items-center border-none p-0 gap-2">
                                    <draggable
                                        :list="form.features"
                                        item-key="id"
                                        class="flex items-center flex-wrap border-none p-0 gap-2"
                                        ghost-class="opacity-35"
                                        @start="dragging = true"
                                        @end="dragging = false"
                                    >
                                        <template #item="{ element }">
                                            <div class="h-auto bg-muted rounded">
                                                <div class="flex items-center justify-start gap-2 ps-2 pe-3 py-2">
                                                    <GripVertical class="h-3 w-3 opacity-30 hover:opacity-100 hover:cursor-move drag-handle" />
                                                    <div v-if="element.icon" class="feature-icon-dashboard" v-html="element.icon"></div>
                                                    <CheckIcon v-else class="h-4 w-4 stroke-2" />
                                                    <span class="me-4">
                                                        {{ element.name }}
                                                    </span>
                                                    <Button 
                                                        type="button"
                                                        variant="outline" 
                                                        size="icon" 
                                                        class="h-auto w-auto mt-0.5 bg-transparent shadow-none border-none opacity-50 hover:opacity-100"
                                                        @click="removeFeature(element)"
                                                    >
                                                        <X class="w-4 h-4" />
                                                    </Button>
                                                </div>
                                            </div>
                                        </template>
                                    </draggable>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-auto text-right">
                        <Button :disabled="form.processing">Save</Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
