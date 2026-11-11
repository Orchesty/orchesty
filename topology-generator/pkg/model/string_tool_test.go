package model

import (
	"testing"
)

const testString = "Lorem ipsum dolor sit amet, consectetuer adipiscing elit."

func TestSubstring_Substring(t *testing.T) {
	t.Run("TestSubstring command", func(t *testing.T) {
		t.Run("Test start is greater", substringStartGreater)
		t.Run("Test get part from start", substringFromBegin)
		t.Run("Test get part from inner", substringFromInner)
		t.Run("Test get part from inner length 10", substringFromInnerLength)
		t.Run("Test get part from inner length greater start", substringLengthGraterStart)
		t.Run("Test get part from inner length plus start is greater len", substringLengthStartGreater)
	})
}

func TestCreateServiceName_CreateServiceName(t *testing.T) {
	t.Run("Test CreateServiceName", func(t *testing.T) {
		t.Run("Test normal", serviceNameOk)
		t.Run("Test normal long version", serviceNameOkLong)
	})
}

func serviceNameOkLong(t *testing.T) {
	result := CreateServiceName("5cc0474e4e9acc00282bb942-testovaci-foo-bar")

	if result != "5cc0474e4e9acc00282bb942-tes-foo-bar" {
		t.Errorf("bad result: excpected `5cc0474e4e9acc00282bb942-tes` got `%s`", result)
	}
}

func serviceNameOk(t *testing.T) {
	result := CreateServiceName("5cc0474e4e9acc00282bb942-testovaci")

	if result != "5cc0474e4e9acc00282bb942-tes" {
		t.Errorf("bad result: excpected `5cc0474e4e9acc00282bb942-tes` got `%s`", result)
	}
}

func substringLengthGraterStart(t *testing.T) {
	result := Substring(testString, 20, 22)

	if result != "t amet, consectetuer a" {
		t.Errorf("bad result: expected `t amet, consectetuer a`, got `%s`", result)
	}
}

func substringFromInnerLength(t *testing.T) {
	result := Substring(testString, 20, 10)

	if result != "t amet, co" {
		t.Errorf("bad result: expected `t amet, co`, got `%s`", result)
	}
}

func substringFromInner(t *testing.T) {
	result := Substring(testString, 20, 0)

	if result != "t amet, consectetuer adipiscing elit." {
		t.Errorf("bad result: expected `t amet, consectetuer adipiscing elit.`, got `%s`", result)
	}
}

func substringFromBegin(t *testing.T) {
	result := Substring(testString, 0, 10)

	if result != "Lorem ipsu" {
		t.Errorf("bad result: expected `Lorem ipsu`, got `%s`", result)
	}
}

func substringStartGreater(t *testing.T) {
	result := Substring(testString, 58, 10)

	if result != "" {
		t.Errorf("bad result")
	}
}

func substringLengthStartGreater(t *testing.T) {
	result := Substring(testString, 50, 10)

	if result != "g elit." {
		t.Errorf("bad result: expected `g elit.`, got `%s`", result)
	}
}
