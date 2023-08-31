<script>
import { mapActions } from "vuex"
import { TOPOLOGIES } from "@/store/modules/topologies/types"
import { IMPLEMENTATIONS } from "@/store/modules/implementations/types"
import { EVENTS, events } from "@/services/utils/events"
import FlashMessageMixin from "@/services/mixins/FlashMessageMixin"

export default {
  name: "ImportTopologyMixin",
  mixins: [FlashMessageMixin],
  data() {
    return {
      topologyMixinImplementations: {},
      folderId: null,
    }
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.TOPOLOGY.SAVE_DIAGRAM,
      TOPOLOGIES.ACTIONS.TOPOLOGY.CREATE,
      TOPOLOGIES.ACTIONS.TOPOLOGY.SET_LOCAL_DIAGRAM,
      TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES,
      TOPOLOGIES.ACTIONS.TOPOLOGY.GET_DIAGRAM,
    ]),
    ...mapActions(IMPLEMENTATIONS.NAMESPACE, [
      IMPLEMENTATIONS.ACTIONS.SET_FILE_IMPLEMENTATIONS,
    ]),

    async saveTopology(form, xml) {
      const response = await this[TOPOLOGIES.ACTIONS.TOPOLOGY.CREATE](form)
      await this[TOPOLOGIES.ACTIONS.TOPOLOGY.SAVE_DIAGRAM]({
        id: response._id,
        xml: xml,
      })
      await this[TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]()
      this.isOpen = false
    },

    async replaceTopologyData(e, altName = "", folderId, setDiagramState) {
      const reader = new FileReader()
      const file = e.target.files[0]
      let name = file.name.split(".")[0]
      let form = {
        name: altName ? altName : name,
        description: null,
        folder: folderId ? folderId : null,
      }
      reader.onload = async (event) => {
        const parser = new DOMParser()
        let xmlDoc = parser.parseFromString(event.target.result, "text/xml")
        if (altName) {
          for (
            let i = 0;
            i <
            xmlDoc.getElementsByTagName("bpmn:process")[0].attributes.length;
            i++
          ) {
            if (
              xmlDoc.getElementsByTagName("bpmn:process")[0].attributes[i]
                .name === "id"
            ) {
              xmlDoc.getElementsByTagName("bpmn:process")[0].attributes[
                i
              ].value = altName
            }
          }
        }
        for (
          let h = 0;
          h < xmlDoc.getElementsByTagName("bpmn:process")[0].children.length;
          h++
        ) {
          let it = xmlDoc.getElementsByTagName("bpmn:process")[0].children[h]
          for (let i = 0; i < it.attributes.length; i++) {
            if (it.attributes[i].name === "pipes:sdkHost") {
              this.implementationsFile.forEach((file) => {
                if (it.attributes[i].value === file.name) {
                  it.attributes[i].value = file.replace
                }
              })
            }
          }
        }
        if (setDiagramState) {
          await this[TOPOLOGIES.ACTIONS.TOPOLOGY.SET_LOCAL_DIAGRAM](
            new XMLSerializer().serializeToString(xmlDoc)
          )
          this.isOpen = false
        } else {
          await this.saveTopology(
            form,
            new XMLSerializer().serializeToString(xmlDoc)
          )
        }
        this.showFlashMessage(false, "Topoplogy imported")
      }
      reader.readAsText(file)
    },
    fetchTopologyDiagram(e, folderId, setDiagramState = false) {
      const reader = new FileReader()
      const file = e.target.files[0]
      reader.onload = (event) => {
        let services = []
        const parser = new DOMParser()
        let xmlDoc = parser.parseFromString(event.target.result, "text/xml")

        if (!xmlDoc.getElementsByTagName("bpmn:process")[0]) {
          this.showFlashMessage(
            true,
            "Error reading the file. Please check your file format/encoding."
          )
          return
        }
        for (
          let i = 0;
          i < xmlDoc.getElementsByTagName("bpmn:process")[0].children.length;
          i++
        ) {
          let it = xmlDoc.getElementsByTagName("bpmn:process")[0].children[i]
          for (let i = 0; i < it.attributes.length; i++) {
            if (it.attributes[i].name === "pipes:sdkHost") {
              services.push(it.attributes[i].nodeValue)
            }
          }
        }
        this[IMPLEMENTATIONS.ACTIONS.SET_FILE_IMPLEMENTATIONS]([
          ...new Set(services),
        ])
        events.emit(EVENTS.MODAL.TOPOLOGY.IMPORT, {
          e,
          folderId,
          setDiagramState,
        })
      }
      reader.readAsText(file)
    },
  },
}
</script>
