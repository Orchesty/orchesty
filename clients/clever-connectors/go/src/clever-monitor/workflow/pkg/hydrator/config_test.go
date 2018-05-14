package hydrator

import (
	"testing"
	"github.com/stretchr/testify/assert"
	"io/ioutil"
)

const editorSimpleExampleFile = "../../examples/editor_1.json"

func TestStringToEditorConfig(t *testing.T) {
	str := getExampleFileJson(t, editorSimpleExampleFile)

	conf, err := StringToEditorConfig(str)
	assert.Nil(t, err)
	assert.Len(t, conf.Items, 6)

	assert.Equal(t, "root", conf.Items[0].Id)
	assert.Equal(t, "", conf.Items[0].ParentId)

	assert.Equal(t, "1", conf.Items[1].Id)
	assert.Equal(t, "root", conf.Items[1].ParentId)

	assert.Equal(t, "2", conf.Items[2].Id)
	assert.Equal(t, "1", conf.Items[2].ParentId)

	assert.Equal(t, "3", conf.Items[3].Id)
	assert.Equal(t, "2", conf.Items[3].ParentId)

	assert.Equal(t, "4", conf.Items[4].Id)
	assert.Equal(t, "3", conf.Items[4].ParentId)

	assert.Equal(t, "5", conf.Items[5].Id)
	assert.Equal(t, "4", conf.Items[5].ParentId)
}

func getExampleFileJson(t *testing.T, file string) string {
	assert.FileExists(t, file)

	b, err := ioutil.ReadFile(file)
	assert.Nil(t, err)
	assert.True(t, len(string(b)) > 0)

	return string(b)
}
