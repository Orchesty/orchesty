import { TOPOLOGY_ENUMS } from "@/services/enums/topologyEnums"

export function getTopologyVersionString(topology) {
  return TOPOLOGY_ENUMS.TOPOLOGY === topology.type
    ? `v.${topology.version}`
    : ""
}

export function getTopologyName(topology, withVersion = false) {
  if (withVersion)
    return `${topology.name} ${getTopologyVersionString(topology)}`
  return topology.name
}
