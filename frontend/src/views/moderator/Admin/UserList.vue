<template>
  <ModeratorNavigationLayout
    :currentRouteTitle="$t('moderator.view.userList.header')"
  >
    <template v-slot:content>
      <div class="confirm__content full-height-header">
        <h1>{{ $t('moderator.view.userList.header') }}</h1>
        <el-table
          v-if="userList && userList.length > 0"
          :data="userList"
          style="width: 100%"
          max-height="250"
        >
          <el-table-column
            prop="username"
            :label="$t('moderator.view.userList.username')"
          />
          <el-table-column
            prop="confirmed"
            :label="$t('moderator.view.userList.confirmed')"
          >
            <template #default="scope">
              <font-awesome-icon
                v-if="scope.row.confirmed"
                class="icon"
                icon="check"
              />
              <font-awesome-icon v-else class="icon" icon="xmark" />
            </template>
          </el-table-column>
          <el-table-column
            prop="ownSessions"
            :label="$t('moderator.view.userList.ownSessions')"
          />
          <el-table-column
            prop="sharedSessions"
            :label="$t('moderator.view.userList.sharedSessions')"
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

  userCash!: cashService.SimplifiedCashEntry<UserState[]>;
  mounted(): void {
    this.userCash = userService.registerGetUserList(this.updateList);
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
</style>
