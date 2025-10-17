<script setup lang="ts">
/* ------------------------------ Imports ------------------------------ */
import AppLayout from '@/layouts/AppLayout.vue'
import HeadingSmall from '@/components/HeadingSmall.vue'
import InputError from '@/components/InputError.vue'

import Button from '@/components/ui/button/Button.vue'
import Input from '@/components/ui/input/Input.vue'
import Label from '@/components/ui/label/Label.vue'
import { Separator } from '@/components/ui/separator'

import Combobox from '@/components/ui/combobox/Combobox.vue'
import ComboboxAnchor from '@/components/ui/combobox/ComboboxAnchor.vue'
import ComboboxTrigger from '@/components/ui/combobox/ComboboxTrigger.vue'
import ComboboxList from '@/components/ui/combobox/ComboboxList.vue'
import ComboboxInput from '@/components/ui/combobox/ComboboxInput.vue'
import ComboboxGroup from '@/components/ui/combobox/ComboboxGroup.vue'
import ComboboxItem from '@/components/ui/combobox/ComboboxItem.vue'
import ComboboxItemIndicator from '@/components/ui/combobox/ComboboxItemIndicator.vue'

import Popover from '@/components/ui/popover/Popover.vue'
import PopoverTrigger from '@/components/ui/popover/PopoverTrigger.vue'
import PopoverContent from '@/components/ui/popover/PopoverContent.vue'

import Calendar from '@/components/ui/calendar/Calendar.vue'

import { cn } from '@/lib/utils'
import { type BreadcrumbItem } from '@/types'

import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { debounce } from 'lodash-es'

import { CalendarIcon, Check, ChevronRight, ChevronsUpDown, Search, X } from 'lucide-vue-next'

import dayjs from 'dayjs'
import utc from 'dayjs/plugin/utc'
import timezone from 'dayjs/plugin/timezone'

import {
	today,
	getLocalTimeZone,
	type DateValue,
	parseDate
} from '@internationalized/date'

import {
	Dialog,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
	DialogTrigger,
} from '@/components/ui/dialog'
import DialogClose from '@/components/ui/dialog/DialogClose.vue'
import { Textarea } from '@/components/ui/textarea'

/* ------------------------------ Day.js Setup ------------------------------ */
dayjs.extend(utc)
dayjs.extend(timezone)

/* ------------------------------ Interfaces ------------------------------ */
interface CountryOption {
	country: string      // e.g., "ID"
	countryName: string  // e.g., "Indonesia"
	code: string         // e.g., "+62"
}

interface PhoneField {
	country: CountryOption
	number: string
}

/* ------------------------------ State ------------------------------ */
const page = usePage()
const props = page.props as any

const reservation = props.reservation
const reservationDays = props.reservationDays

const countries = ref<CountryOption[]>([])

const comboboxCountrySearchTerm = ref('')

const selectedCountry = ref<CountryOption>(reservation.phone?.country ?? {
	country: 'ID',
	countryName: 'Indonesia',
	code: '+62'
})

const statuses = [
	{ value: 'pending', label: 'Pending' },
	{ value: 'confirmed', label: 'Confirmed' },
	{ value: 'cancelled', label: 'Cancelled' },
	{ value: 'checked_in', label: 'Checked In' },
	{ value: 'checked_out', label: 'Checked Out' },
	{ value: 'expired', label: 'Expired' },
]

const paymentStatuses = [
	{ value: 'unpaid', label: 'Unpaid' },
	{ value: 'paid', label: 'Paid' },
	{ value: 'refunded', label: 'Refunded' },
	{ value: 'expired', label: 'Expired' },
]

const sources = [
	{ value: 'direct', label: 'Direct (Website/Walk-in)' },
	{ value: 'agency', label: 'Travel Agency (Offline)' },
	{ value: 'ota', label: 'Online Travel Agent (Booking, Agoda, Airbnb, etc.)' },
	{ value: 'other', label: 'Other' },
]

const statusOption = statuses.find(s => s.value === reservation.status) ?? statuses[0]
const paymentStatusOption = paymentStatuses.find(s => s.value === reservation.payment_status) ?? paymentStatuses[0]
const sourceOption = sources.find(s => s.value === reservation.source) ?? sources[0]

/* ------------------------------ Form ------------------------------ */
const form = useForm({
	status: statusOption,
	payment_status: paymentStatusOption,
	source: sourceOption,

	unit: reservation.unit,
	check_in: reservation.check_in,
	check_out: reservation.check_out,
	qty: reservation.qty,
	guests: reservation.guests,
	rate: reservation.rate,
	total_price: reservation.total_price,
	currency: reservation.currency,

	first_name: reservation.first_name ?? '',
	last_name: reservation.last_name ?? '',
	email: reservation.email ?? '',
	phone: {
		country: selectedCountry.value,
		number: reservation.phone?.number ?? ''
	} as PhoneField,
	notes: reservation.notes ?? '',
})

/* ------------------------------ Breadcrumbs ------------------------------ */
const breadcrumbs: BreadcrumbItem[] = [
	{ title: 'Reservations', href: '/reservations' },
	{ title: 'Edit Reservation', href: '/reservations/create' }
]

/* ------------------------------ Phone Handling ------------------------------ */
const MAX_PHONE_LENGTH = 15

function onPhoneInput(e: Event) {
	let input = (e.target as HTMLInputElement).value

	input = input.replace(/\D/g, '')       // Remove non-digit characters
	input = input.replace(/^0+/, '')       // Remove leading zeros

	if (input.length > MAX_PHONE_LENGTH) {
		input = input.slice(0, MAX_PHONE_LENGTH)
	}

	form.phone.number = input
}
/* ------------------------------ Utilities ------------------------------ */
const formatRupiah = (num: number | string | undefined) => {
	const n = Number(num ?? 0)

	const formatted = new Intl.NumberFormat('en-US', {
		style: 'currency',
		currency: 'IDR',
		minimumFractionDigits: 0
	}).format(n)

	return formatted.replace("IDR", "Rp")
}

/* ------------------------------ Fetch Combobox Data ------------------------------ */
const fetchCountriesComboboxData = debounce(() => {
	router.get(
		route('reservations.edit', reservation.id),
		{ search: comboboxCountrySearchTerm.value },
		{
			preserveScroll: true,
			preserveState: true,
			only: ['countries'],
			onSuccess: () => {
				const newProps = usePage().props.countries as CountryOption[] ?? []
				countries.value = newProps
			}
		}
	)
}, 300)

watch(comboboxCountrySearchTerm, (term) => {
	if (!term) {
		countries.value = []
		return
	}
	fetchCountriesComboboxData()
})

/* ------------------------------ Methods ------------------------------ */
const submit = () => {
	form.put(route('reservations.update', reservation.id), {
		preserveScroll: false,
		onSuccess: () => {
			toast('Reservation updated', {
				description: 'The reservation has been updated successfully',
				action: { label: 'Close' }
			})
		},
		onError: () => {
			toast('Error creating reservation', {
				description: 'Something went wrong, please try again',
				class: 'toast-destructive',
				action: { label: 'Close' },
			})
		}
		// onError: (errors) => {
        //     toast('Error updating unit', {
        //         description: Object.values(errors)[0] || 'Something went wrong, please try again',
        //         // description: 'Something went wrong, please try again',
        //         action: { label: 'Close' },
        //     });
        // },
	})
}

const handleKeydown = (e: KeyboardEvent) => {
	const isMac = navigator.platform.toUpperCase().includes('MAC')
	const isSaveShortcut =
		(isMac && e.metaKey && e.key === 's') ||
		(!isMac && e.ctrlKey && e.key === 's')

	if (isSaveShortcut) {
		e.preventDefault() // prevent browser's default "save" behavior
		submit()
	}
}

onMounted(() => {
	window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
	window.removeEventListener('keydown', handleKeydown)
})
</script>

<template>
    <Head title="Edit Reservation" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex flex-col space-y-6 min-h-full">
                <!-- <HeadingSmall
					title="Edit reservation"
					description="Manually create a new reservation for a unit and assign the appropriate rate"
				/>

                <Separator /> -->

                <form @submit.prevent="submit" class="space-y-6 h-full flex flex-col">
                    <!-- <pre class="text-xs">
						{{ reservation }}
					</pre> -->
					<!-- Unit -->
					<div class="grid gap-2">
						<Label>Reservation Code</Label>
						<div class="mt-1 flex items-center gap-2 h-9 border rounded-md shadow-xs bg-muted text-sm px-3 text-muted-foreground">
							{{ reservation.reservation_code }}
						</div>
					</div>

					<!-- Booked On -->
					<div class="grid gap-2">
						<Label>Booked On</Label>
						<div class="mt-1 flex items-center gap-2 h-9 border rounded-md shadow-xs bg-muted text-sm px-3 text-muted-foreground">
							{{ dayjs(reservation.booked_on).tz(dayjs.tz.guess()).format("DD MMM YYYY") }}
						</div>
					</div>

					<!-- Status -->
                    <div class="grid gap-2">
                        <Label>Status</Label>
                        <Combobox 
                            v-model="form.status"
                            class="mt-1"
                        >
                            <ComboboxAnchor as-child>
                                <ComboboxTrigger as-child>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        class="justify-between w-full"
                                    >
                                        {{ form.status?.label ?? 'Select a status for the reservation' }}
                                        <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                    </Button>
                                </ComboboxTrigger>
                            </ComboboxAnchor>

                            <ComboboxList align="start" class="w-full min-w-[250px]">
                                <ComboboxGroup>
                                    <ComboboxItem
                                        v-for="(status, index) in statuses"
                                        :key="index"
                                        :value="status"
                                        class="w-full min-w-[250px] flex items-center justify-between"
                                    >
                                        {{ status.label }}
                                        <ComboboxItemIndicator>
                                            <Check :class="cn('ml-auto h-4 w-4')" />
                                        </ComboboxItemIndicator>
                                    </ComboboxItem>
                                </ComboboxGroup>
                            </ComboboxList>
                        </Combobox>
                        <InputError :message="form.errors.status" />
                    </div>

					<!-- Payment Status -->
                    <div class="grid gap-2">
                        <Label>Payment Status</Label>
                        <Combobox 
                            v-model="form.payment_status"
                            class="mt-1"
                        >
                            <ComboboxAnchor as-child>
                                <ComboboxTrigger as-child>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        class="justify-between w-full"
                                    >
                                        {{ form.payment_status?.label ?? 'Select a payment status for the reservation' }}
                                        <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                    </Button>
                                </ComboboxTrigger>
                            </ComboboxAnchor>

                            <ComboboxList align="start" class="w-full min-w-[250px]">
                                <ComboboxGroup>
                                    <ComboboxItem
                                        v-for="(status, index) in paymentStatuses"
                                        :key="index"
                                        :value="status"
                                        class="w-full min-w-[250px] flex items-center justify-between"
                                    >
                                        {{ status.label }}
                                        <ComboboxItemIndicator>
                                            <Check :class="cn('ml-auto h-4 w-4')" />
                                        </ComboboxItemIndicator>
                                    </ComboboxItem>
                                </ComboboxGroup>
                            </ComboboxList>
                        </Combobox>
                        <InputError :message="form.errors.payment_status" />
                    </div>

					<!-- Source -->
                    <div class="grid gap-2">
                        <Label>Source</Label>
                        <Combobox 
                            v-model="form.source"
                            class="mt-1"
                        >
                            <ComboboxAnchor as-child>
                                <ComboboxTrigger as-child>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        class="justify-between w-full"
                                    >
                                        {{ form.source?.label ?? 'Select a source for the reservation' }}
                                        <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                    </Button>
                                </ComboboxTrigger>
                            </ComboboxAnchor>

                            <ComboboxList align="start" class="w-full min-w-[250px]">
                                <ComboboxGroup>
                                    <ComboboxItem
                                        v-for="(source, index) in sources"
                                        :key="index"
                                        :value="source"
                                        class="w-full min-w-[250px] flex items-center justify-between"
                                    >
                                        {{ source.label }}
                                        <ComboboxItemIndicator>
                                            <Check :class="cn('ml-auto h-4 w-4')" />
                                        </ComboboxItemIndicator>
                                    </ComboboxItem>
                                </ComboboxGroup>
                            </ComboboxList>
                        </Combobox>
                        <InputError :message="form.errors.source" />
                    </div>

					<!-- Unit -->
					<div class="grid gap-2">
						<Label>Unit</Label>
						<div class="mt-1 flex items-center gap-2 h-9 border rounded-md shadow-xs bg-muted text-sm px-3 text-muted-foreground">
							{{ reservation.unit.label }}
						</div>
					</div>

					<!-- Check-in -->
					<div class="grid gap-2">
						<Label>Check-in</Label>
						<div class="mt-1 flex items-center gap-2 h-9 border rounded-md shadow-xs bg-muted text-sm px-3 text-muted-foreground">
							{{ dayjs(reservation.check_in).tz(dayjs.tz.guess()).format("DD MMM YYYY") }}
						</div>
					</div>

					<!-- Check-out -->
					<div class="grid gap-2">
						<Label>Check-out</Label>
						<div class="mt-1 flex items-center gap-2 h-9 border rounded-md shadow-xs bg-muted text-sm px-3 text-muted-foreground">
							{{ dayjs(reservation.check_out).tz(dayjs.tz.guess()).format("DD MMM YYYY") }}
						</div>
					</div>

					<!-- Qty -->
                    <div class="grid gap-2">
                        <Label>Qty</Label>
						<div class="mt-1 flex items-center gap-2 h-9 border rounded-md shadow-xs bg-muted text-sm px-3 text-muted-foreground">
							{{ reservation.qty }}
						</div>
                    </div>

					<!-- Guests -->
                    <div class="grid gap-2">
                        <Label>Guests</Label>
						<div class="mt-1 flex items-center gap-2 h-9 border rounded-md shadow-xs bg-muted text-sm px-3 text-muted-foreground">
							{{ reservation.guests }}
						</div>
                    </div>

					<!-- Rate -->
                    <div class="grid gap-2">
                        <Label>Rate</Label>
						<div class="mt-1 flex items-center gap-2 h-9 border rounded-md shadow-xs bg-muted text-sm px-3 text-muted-foreground">
							{{ reservation.rate.label }}
						</div>
                    </div>

					<!-- Total Price -->
					<div class="grid gap-2">
						<Label>Total Price</Label>
						<div class="flex items-center gap-2 mt-1">
							<div class="flex items-center h-9 border rounded-md shadow-xs bg-muted text-sm px-3 text-muted-foreground w-full">
								{{ formatRupiah(reservation.total_price) }}
							</div>
							<Dialog v-if="reservation.total_price > 0">
								<DialogTrigger>
									<Button type="button" variant="outline">
										Breakdown
										<ChevronRight class="mt-0.5" />
									</Button>
								</DialogTrigger>
								<DialogContent class="p-0">
									<DialogHeader class="px-6 pt-6 pb-0">
										<DialogTitle>{{ reservation.rate?.label ?? 'Price breakdown' }}</DialogTitle>
										<DialogDescription>
											{{ dayjs(reservation.check_in).tz(dayjs.tz.guess()).format("DD MMM YYYY") }} - {{ dayjs(reservation.check_out).tz(dayjs.tz.guess()).format("DD MMM YYYY") }}
										</DialogDescription>
									</DialogHeader>

									<div class="border divide-y" v-if="reservationDays.length">
										<div 
											class="flex items-center justify-between gap-2 text-sm px-6 py-4" 
											v-for="day in reservationDays"
											:key="`${day.date}-${day.rate_id}`"
										>
											<div>
												<h1 class="leading-none">
													{{ dayjs(day.date).tz(dayjs.tz.guess()).format("DD MMM YYYY") }}
												</h1>
												<div class="text-xs text-muted-foreground mt-1 flex items-center gap-1">
													<span>{{ formatRupiah(day.total_with_tax) }} (incl. Tax 10%)</span>
													<div>
														x{{ reservation.qty }} {{ reservation.unit.type }}{{ form.qty > 1 ? 's' : '' }}
													</div>
												</div>
											</div>
											<div class="ms-auto">
												{{ formatRupiah(day.total_with_tax * (Number(form.qty) || 1)) }}
											</div>
										</div>
										<div
											class="flex items-center justify-between gap-2 text-sm px-6 py-[23px]" 
										>
											<h1 class="font-medium">
												Total price
											</h1>
											<div class="ms-auto font-semibold">
												{{ formatRupiah(reservation.total_price) }}
											</div>
										</div>
									</div>
									<div v-else class="border px-6 py-6 text-sm text-muted-foreground text-center">
										No price breakdown yet.
									</div>

									<DialogFooter class="px-6 pb-6 pt-0">
										<DialogClose as-child>
											<Button variant="outline">
												Close
											</Button>
										</DialogClose>
									</DialogFooter>
								</DialogContent>
							</Dialog>
						</div>
					</div>

					<!-- Guest Info -->
					<div class="grid gap-2">
						<Label for="firstName">First Name</Label>
						<Input
							id="firstName"
							class="mt-1 block w-full"
							v-model="form.first_name"
							autocomplete="name"
							placeholder="Guest first name (e.g. John)"
						/>
						<InputError :message="form.errors.first_name" />
					</div>

					<div class="grid gap-2">
						<Label for="lastName">Last Name</Label>
						<Input
							id="lastName"
							class="mt-1 block w-full"
							v-model="form.last_name"
							autocomplete="name"
							placeholder="Guest last name (e.g. Doe)"
						/>
						<InputError :message="form.errors.last_name" />
					</div>

					<div class="flex flex-col gap-2">
						<Label for="email">Email</Label>
						<Input
							id="email"
							type="email"
							class="mt-1 block w-full"
							v-model="form.email"
							autocomplete="email"
							placeholder="Guest email (e.g. johndoe@example.com)"
						/>
						<InputError :message="form.errors.email" />
					</div>

					<!-- Phone -->
					<div class="grid gap-2">
						<Label for="phone">Phone</Label>
						<div class="mt-1 flex items-center justify-start gap-2">
							<Combobox v-model="selectedCountry">
								<ComboboxAnchor as-child>
									<ComboboxTrigger as-child>
										<Button
											type="button"
											variant="outline"
											class="justify-between w-[90px]"
										>
											{{ selectedCountry?.code ?? 'Select country code' }}
											<ChevronsUpDown class="ml-0 h-4 w-4 shrink-0 opacity-50" />
										</Button>
									</ComboboxTrigger>
								</ComboboxAnchor>

								<ComboboxList align="start" class="w-full min-w-[200px]">
									<div class="relative w-full max-w-sm items-center combobox-input-wrapper">
										<ComboboxInput
											v-model="comboboxCountrySearchTerm"
											placeholder="Search country..."
										/>
										<span class="absolute start-0 inset-y-0 flex items-center justify-center px-3">
											<Search class="size-4 text-muted-foreground" />
										</span>
									</div>

									<ComboboxGroup :class="countries.length < 1 ? 'p-0 border-none' : 'border-t'">
										<ComboboxItem
											v-for="(country, index) in countries"
											:key="country.country"
											:value="country"
										>
											{{ country.code }} ({{ country.country }}) - {{ country.countryName }}
											<ComboboxItemIndicator>
												<Check :class="cn('ml-auto h-4 w-4')" />
											</ComboboxItemIndicator>
										</ComboboxItem>
									</ComboboxGroup>
								</ComboboxList>
							</Combobox>

							<Input
								id="phone"
								v-model="form.phone.number"
								type="text"
								inputmode="numeric"
								placeholder="Enter phone number"
								maxlength="15"
								@input="onPhoneInput"
							/>
						</div>
						<InputError :message="form.errors['phone.number']" />
					</div>

					<!-- Notes -->
					<div class="grid gap-2">
						<Label for="notes">Notes</Label>
						<Textarea
							id="notes"
							class="mt-1 block w-full resize-none min-h-52"
							v-model="form.notes"
							autocomplete="notes"
							placeholder="Notes for this reservation"
						/>
						<InputError :message="form.errors.notes" />
					</div>

					<!-- Save -->
					<div class="mt-auto text-right">
						<Button :disabled="form.processing">Save</Button>
					</div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
