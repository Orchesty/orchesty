package swarm

func (h *Swarm) Close() {
	h.Db.Close()
}
