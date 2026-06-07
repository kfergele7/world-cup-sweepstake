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
        <div class="rounded-lg border border-zinc-200 bg-white p-4">
            <dt class="text-sm font-medium text-zinc-600">Sweepstakes</dt>
            <dd class="mt-1 text-2xl font-semibold text-zinc-950">{{ totals.sweepstakes }}</dd>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4">
            <dt class="text-sm font-medium text-zinc-600">Paid members</dt>
            <dd class="mt-1 text-2xl font-semibold text-zinc-950">{{ totals.paidMembers }}</dd>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4">
            <dt class="text-sm font-medium text-zinc-600">Assignments</dt>
            <dd class="mt-1 text-2xl font-semibold text-zinc-950">{{ totals.assignments }}</dd>
        </div>
    </dl>
</template>
