package json

type Enum interface {
	name() string
	ordinal() int
	valueOf() *[]string
}

type ConfigType uint

func (ct ConfigType) name() string {
	return configTypes[ct]
}
func (ct ConfigType) ordinal() int {
	return int(ct)
}
func (ct ConfigType) String() string {
	return configTypes[ct]
}
func (ct ConfigType) values() *[]string {
	return &configTypes
}

const (
	EDITOR ConfigType = iota
	WORKFLOW
)

var configTypes = []string{"EDITOR", "WORKFLOW"}