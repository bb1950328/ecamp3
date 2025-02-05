import ApiCheckbox from '../ApiCheckbox.vue'
import ApiWrapper from '@/components/form/api/ApiWrapper.vue'
import Vue from 'vue'
import Vuetify from 'vuetify'
import flushPromises from 'flush-promises'
import formBaseComponents from '@/plugins/formBaseComponents'
import merge from 'lodash/merge'
import { ApiMock } from '@/components/form/api/__tests__/ApiMock'
import { i18n } from '@/plugins'
import { mount as mountComponent } from '@vue/test-utils'
import { waitForDebounce } from '@/test/util'

Vue.use(Vuetify)
Vue.use(formBaseComponents)

describe('An ApiCheckbox', () => {
  let vuetify
  let wrapper
  let apiMock

  const path = 'test-field/123'

  beforeEach(() => {
    vuetify = new Vuetify()
    apiMock = ApiMock.create()
  })

  afterEach(() => {
    jest.restoreAllMocks()
    wrapper.destroy()
  })

  const mount = (options) => {
    const app = Vue.component('App', {
      components: { ApiCheckbox },
      props: {
        path: { type: String, default: path },
      },
      template: `
        <div data-app>
          <api-checkbox
            :auto-save="false"
            :path="path"
            uri="test-field/123"
            label="Test field"
            required="true"
          />
        </div>
      `,
    })
    apiMock.get().thenReturn(ApiMock.success(true).forPath(path))
    const defaultOptions = {
      mocks: {
        $tc: () => {},
        api: apiMock.getMocks(),
      },
    }
    return mountComponent(app, {
      vuetify,
      i18n,
      attachTo: document.body,
      ...merge(defaultOptions, options),
    })
  }

  test('triggers api.patch and status update if input changes', async () => {
    apiMock.patch().thenReturn(ApiMock.success(false))
    wrapper = mount()

    await flushPromises()

    const input = wrapper.find('input')
    await input.trigger('click')
    await input.trigger('submit')

    await waitForDebounce()
    await flushPromises()

    expect(apiMock.getMocks().patch).toBeCalledTimes(1)
    expect(wrapper.findComponent(ApiWrapper).vm.localValue).toBe(false)
  })

  test('updates state if value in store is refreshed and has new value', async () => {
    wrapper = mount()
    apiMock.get().thenReturn(ApiMock.success(false).forPath(path))

    wrapper.findComponent(ApiWrapper).vm.reload()

    await waitForDebounce()
    await flushPromises()

    expect(wrapper.findComponent(ApiWrapper).vm.localValue).toBe(false)
    expect(
      wrapper.find('input[type=checkbox]').element.getAttribute('aria-checked')
    ).toBe('false')
  })
})
