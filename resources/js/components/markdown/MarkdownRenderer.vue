<script setup lang="ts">
import DOMPurify from 'dompurify';
import MarkdownIt from 'markdown-it';
import { onMounted, ref, watch } from 'vue';

interface Props {
    content: string;
}

const props = defineProps<Props>();
const html = ref('');

const md = new MarkdownIt({
    html: false,
    linkify: true,
    breaks: true,
});

function render() {
    const unsafe = md.render(props.content || '');
    html.value = DOMPurify.sanitize(unsafe);
}

onMounted(render);
watch(() => props.content, render);
</script>

<template>
    <div class="prose prose-neutral max-w-none" v-html="html" />
</template>
