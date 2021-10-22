<template>
  <div class="confirm__content full-height-header">
    <h1>{{ $t('moderator.view.confirmEmail.header') }}</h1>
    <p class="profile__email">
      {{ $t('moderator.view.confirmEmail.info') }} {{ email }}
    </p>
    <button class="btn btn--gradient" @click="confirm">
      {{ $t('moderator.view.confirmEmail.submit') }}
    </button>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import jwt_decode from 'jwt-decode';

@Options({
  components: {},
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ConfirmEmail extends Vue {
  @Prop() readonly token!: string;
  email = '';

  mounted(): void {
    const decoded = jwt_decode(this.token) as any;
    this.email = decoded.username;
  }

  confirm(): void {
    //todo: implement backend to confirm email
    this.$router.push({ name: 'home' });
  }
}
</script>

<style lang="scss" scoped>
.confirm {
  background-color: var(--color-background-gray);

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
</style>
