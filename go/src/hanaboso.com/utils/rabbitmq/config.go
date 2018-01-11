package rabbitmq

type Exchange struct {
	Name     string
	Bindings []Binding
}

func (q *Exchange) AddBinding(binding Binding) {
	q.Bindings = append(q.Bindings, binding)
}

type Queue struct {
	Name       string
	Durable    bool
	AutoDelete bool
	Exclusive  bool
	NoWait     bool
	Bindings   []Binding
}

func (q *Queue) AddBinding(b Binding) {
	q.Bindings = append(q.Bindings, b)
}

type Binding struct {
	Exchange   string
	RoutingKey string
}
