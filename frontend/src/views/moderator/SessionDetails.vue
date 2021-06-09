<template>
    <div class="detail">
        <Sidebar />
        <main class="detail__content">
            <div v-for="topic in 2" :key="topic">
                <TopicExpand>
                    <template v-slot:title>Topic Uno</template>
                    <template v-slot:content>
                        <li
                            v-for="module in 2"
                            :key="module"
                            class="detail__module"
                        >
                            <ModuleItem type="voting" />
                        </li>
                    </template>
                </TopicExpand>
            </div>
        </main>
    </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import Sidebar from '@/components/moderator/organisms/Sidebar.vue';
import ModuleItem from '@/components/moderator/molecules/ModuleItem.vue';
import TopicExpand from '@/components/shared/atoms/TopicExpand.vue';

@Options({
    components: {
        Sidebar,
        ModuleItem,
        TopicExpand,
    },
})
export default class SessionDetails extends Vue {
    public topicExpanded = true;
}
</script>

<style lang="scss" scoped>
.detail {
    margin-left: var(--sidebar-width);

    &__content {
        flex-grow: 1;
        padding: 2rem 3rem;
    }

    &__module + .detail__module {
        margin-top: 1.5rem;
    }
}
</style>
