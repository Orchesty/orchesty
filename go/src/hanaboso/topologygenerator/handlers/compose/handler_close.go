package compose

func (h *DockerCompose) Close() {
	h.Db.Close()
}
