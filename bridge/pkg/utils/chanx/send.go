package chanx

func TrySend[T interface{}](channel chan T, item T) {
	select {
	case channel <- item:
	default:
	}
}
