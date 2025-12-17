package chanx

func TryAwait[T interface{}](channel chan T) {
	select {
	case <-channel:
	default:
	}
}
