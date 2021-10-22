<template>
  <div class="participant-background">
    <div
      class="
        participant-container
        join
        full-height
        centered-horizontal
        flex-column
      "
    >
      <main>
        <h1 class="heading heading--big heading--white">
          {{ $t('participant.view.join.header') }}
        </h1>
        <p class="join__text">
          {{ $t('participant.view.join.info') }}
        </p>
        <ValidationForm
          :form-data="formData"
          submit-label-key="participant.view.join.submit"
          v-on:submitDataValid="connectToSession"
        >
          <el-form-item
            prop="connectionKey"
            :rules="[defaultFormRules.ruleRequired]"
          >
            <el-input
              v-model="formData.connectionKey"
              name="connectionKey"
              autocomplete="on"
              :placeholder="$t('participant.view.join.pinInfo')"
            />
          </el-form-item>
        </ValidationForm>
      </main>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Participant, ConnectState } from '@/types/api/Participant';
import * as participantService from '@/services/participant-service';
import * as authService from '@/services/auth-service';
import { getSingleTranslatedErrorMessage } from '@/services/exception-service';
import ValidationForm from '@/components/shared/molecules/ValidationForm.vue';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import { ValidationData } from '@/types/ui/ValidationRule';

@Options({
  components: {
    ValidationForm,
  },
})
export default class ParticipantJoin extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  formData: ValidationData = {
    connectionKey: '',
  };

  mounted(): void {
    const browserKeyLS = authService.getBrowserKey();
    if (browserKeyLS) this.formData.connectionKey = browserKeyLS;
  }

  async connectToSession(): Promise<void> {
    if (this.formData.connectionKey.includes('.')) {
      participantService.reconnect(this.formData.connectionKey).then(
        (queryResult) => {
          this.handleConnectionResult(queryResult);
        },
        (error) => {
          this.formData.stateMessage = getSingleTranslatedErrorMessage(error);
        }
      );
    } else {
      participantService.connect(this.formData.connectionKey).then(
        (queryResult) => {
          this.handleConnectionResult(queryResult);
        },
        (error) => {
          this.formData.stateMessage = getSingleTranslatedErrorMessage(error);
        }
      );
    }
  }

  handleConnectionResult(
    participantData: Partial<Participant> | Participant
  ): boolean {
    if (participantData.participant && participantData.token) {
      if (participantData.participant.state === ConnectState.ACTIVE) {
        authService.setBrowserKey(
          participantData.participant.browserKey as string
        );
        authService.setAccessTokenParticipant(
          participantData.token.accessToken as string
        );
        this.$router.push({
          name: 'participant-overview',
        });
        return true;
      }
    }
    return false;
  }
}
</script>

<style lang="scss" scoped>
.join {
  padding-top: 8vh;
  color: #fff;
  background: var(--color-darkblue);
  background-image: url('~@/assets/illustrations/telescope.png');
  background-position: center;
  background-size: cover;

  &__text {
    margin-bottom: 1rem;
  }
}
</style>
