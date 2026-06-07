<script setup>
defineProps({
    totals: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <dl class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-lg border border-brand-border bg-white p-4 shadow-sm shadow-brand-navy/5">
            <dt class="text-sm font-semibold text-brand-muted">Sweepstakes</dt>
            <dd class="mt-1 text-3xl font-black text-brand-navy">{{ totals.sweepstakes }}</dd>
        </div>

        <div class="rounded-lg border border-green-200 bg-green-50 p-4 shadow-sm shadow-brand-green/10">
            <dt class="text-sm font-semibold text-green-800">Paid entrants</dt>
            <dd class="mt-1 text-3xl font-black text-brand-navy">{{ totals.paidMembers }}</dd>
        </div>

        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 shadow-sm shadow-brand-blue/10">
            <dt class="text-sm font-semibold text-blue-800">Assignments</dt>
            <dd class="mt-1 text-3xl font-black text-brand-navy">{{ totals.assignments }}</dd>
        </div>
    </dl>
</template>
