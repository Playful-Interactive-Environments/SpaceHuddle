<template>
  <div class="confirm__content full-height-header" :lang="$i18n.locale">
    <div v-if="confirmed">
      <h1>{{ $t('moderator.view.confirmEmail.thanks') }}</h1>
      <p class="profile__email">
        {{ $t('moderator.view.confirmEmail.info') }} {{ email }}
      </p>
      <router-link to="/">
        <p role="button" class="link">
          {{ $t('moderator.view.confirmEmail.home') }}
        </p>
      </router-link>
    </div>
    <div v-if="failed" class="error">
      <h1>{{ $t('moderator.view.confirmEmail.failed') }}</h1>
      <p class="profile__email">
        {{ errorMessage }}
      </p>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import jwt_decode from 'jwt-decode';
import * as userService from '@/services/user-service';
import { getSingleTranslatedErrorMessage } from '@/services/exception-service';

enum Confirm {
  WAIT,
  DONE,
  FAILED,
}

@Options({
  components: {},
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ConfirmEmail extends Vue {
  @Prop() readonly token!: string;
  email = '';
  errorMessage = '';
  confirm: Confirm = Confirm.WAIT;

  get confirmed(): boolean {
    return this.confirm === Confirm.DONE;
  }

  get failed(): boolean {
    return this.confirm === Confirm.FAILED;
  }

  mounted(): void {
    try {
      const decoded = jwt_decode(this.token) as any;
      userService.confirmEmail(this.token).then(
        () => {
          this.email = decoded.username;
          this.confirm = Confirm.DONE;
        },
        (error) => {
          this.errorMessage = getSingleTranslatedErrorMessage(error);
          this.confirm = Confirm.FAILED;
        }
      );
    } catch (exception: any) {
      if (exception) this.errorMessage = exception.message;
      this.confirm = Confirm.FAILED;
    }
  }
}
</script>

<style lang="scss" scoped>
.confirm {
  background-color: var(--color-background);

  h1 {
    margin-top: 0;
  }

  &__content {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding-bottom: 3rem;
  }

  &__email {
    margin-bottom: 2rem;
  }
}

.link {
  font-style: italic;
  text-decoration: underline;
}

.error {
  color: var(--el-color-error);
}
</style>
