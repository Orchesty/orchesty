package limiter

type Decider interface {
	Decide(key string, time string, value string) (bool, error)
}

type Limiter struct {

}

func (l *Limiter) Decide(key string, time string, value string) (bool, error) {
	// TODO implement
	// - check cache
	// - check mongo

	return true, nil
}
