package model

type JsonErr struct {
	Code int    `json:"code"`
	Text string `json:"text"`
}

const MONGODB = "mongo_db"
const HTTPWORKER = "worker"
const ACTIONS = "actions"
const DOCKER = "docker"
const APP = "app"

type AppError struct {
	Message string
	Type    string
}
