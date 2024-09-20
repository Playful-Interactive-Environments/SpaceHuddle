<template>
  <ModeratorNavigationLayout
    :currentRouteTitle="$t('moderator.view.userList.header')"
  >
    <template v-slot:content>
      <div ref="content" class="confirm__content full-height-header">
        <div class="level">
          <div class="level-left">
            <h1 class="level-item">
              {{ $t('moderator.view.userList.header') }}
            </h1>
          </div>
          <div class="level-right">
            <div class="level-item">
              <font-awesome-icon class="icon" icon="users" />
              {{ userList.length }}
            </div>
            <div class="level-item">
              <font-awesome-icon
                class="icon"
                icon="check"
                style="color: var(--color-green)"
              />
              {{ userList.filter((item) => item.confirmed).length }}
            </div>
            <div class="level-item">
              <font-awesome-icon
                class="icon"
                icon="xmark"
                style="color: var(--color-red)"
              />
              {{ userList.filter((item) => !item.confirmed).length }}
            </div>
            <div class="level-item">
              <font-awesome-icon class="icon" icon="file" />
              {{ userList.reduce((a, b) => a + b.ownSessions, 0) }}
            </div>
          </div>
        </div>
        <el-table
          v-if="userList && userList.length > 0"
          :data="userList"
          style="width: 100%"
          :max-height="maxHeight - 50"
        >
          <el-table-column
            prop="username"
            :label="$t('moderator.view.userList.username')"
            sortable
          />
          <el-table-column
            prop="creationDate"
            :label="$t('moderator.view.userList.creationDate')"
            sortable
          />
          <el-table-column
            prop="confirmed"
            :label="$t('moderator.view.userList.confirmed')"
            sortable
          >
            <template #default="scope">
              <font-awesome-icon
                v-if="scope.row.confirmed"
                class="icon"
                icon="check"
                style="color: var(--color-green)"
              />
              <font-awesome-icon
                v-else
                class="icon"
                icon="xmark"
                style="color: var(--color-red)"
              />
            </template>
          </el-table-column>
          <el-table-column
            prop="ownSessions"
            :label="$t('moderator.view.userList.ownSessions')"
            sortable
          />
          <el-table-column
            prop="sharedSessions"
            :label="$t('moderator.view.userList.sharedSessions')"
            sortable
          />
          <el-table-column width="170">
            <template #default="scope">
              <el-button
                v-if="!scope.row.confirmed"
                type="primary"
                v-on:click="confirmUser(scope.$index)"
              >
                {{ $t('moderator.view.userList.confirm') }}
              </el-button>
            </template>
          </el-table-column>
        </el-table>
      </div>
    </template>
  </ModeratorNavigationLayout>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import * as userService from '@/services/user-service';
import * as cashService from '@/services/cash-service';
import { UserState } from '@/types/api/UserState';
import ModeratorNavigationLayout from '@/components/moderator/organisms/layout/ModeratorNavigationLayout.vue';

@Options({
  components: { ModeratorNavigationLayout },
  emits: [],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class UserList extends Vue {
  userList: UserState[] = [];
  maxHeight = 100;

  userCash!: cashService.SimplifiedCashEntry<UserState[]>;
  mounted(): void {
    this.userCash = userService.registerGetUserList(this.updateList);
    const content = this.$refs.content as HTMLElement;
    this.maxHeight = content.clientHeight;
  }

  unmounted(): void {
    cashService.deregisterAllGet(this.updateList);
  }

  updateList(list: UserState[]): void {
    this.userList = list;
  }

  async confirmUser(index: number): Promise<void> {
    const user = this.userList[index];
    userService.confirmOtherUser(user.id).then(() => {
      this.userCash.refreshData();
    });
  }
}
</script>

<style lang="scss" scoped>
h1 {
  font-weight: var(--font-weight-semibold);
  font-size: var(--font-size-large);
}

.icon {
  color: var(--color-yellow);
}

.level {
  padding-bottom: 1rem;
}

.level-right {
  .level-item {
    padding-left: 0.5rem;
  }
}
</style>
