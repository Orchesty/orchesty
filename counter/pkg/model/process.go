package model

import (
	"go.mongodb.org/mongo-driver/bson/primitive"
	"time"
)

type Processes map[string]*Process

type Process struct {
	Id            primitive.ObjectID     `bson:"-"`
	CorrelationId string                 `bson:"correlationId"`
	TopologyId    string                 `bson:"topologyId"`
	ProcessId     string                 `bson:"processId"`
	Ok            int                    `bson:"ok"`
	Nok           int                    `bson:"nok"`
	Created       time.Time              `bson:"created"`
	Finished      *time.Time             `bson:"finished"`
	Total         int                    `bson:"total"`
	OpenProcesses int                    `bson:"openProcesses"`
	Subprocesses  map[string]*Subprocess `bson:"subprocesses"`
	LastUpdate    time.Time              `bson:"-"`
}

type Subprocess struct {
	ParentProcess string `bson:"parentProcess"`
	Total         int    `bson:"total"`
	Ok            int    `bson:"ok"`
	Nok           int    `bson:"nok"`
}

type ProcessWithId struct {
	// Simply `Process` does not work -> maybe some bson marking?
	Id            primitive.ObjectID     `bson:"_id"`
	CorrelationId string                 `bson:"correlationId"`
	TopologyId    string                 `bson:"topologyId"`
	ProcessId     string                 `bson:"processId"`
	Ok            int                    `bson:"ok"`
	Nok           int                    `bson:"nok"`
	Created       time.Time              `bson:"created"`
	Finished      *time.Time             `bson:"finished"`
	Total         int                    `bson:"total"`
	OpenProcesses int                    `bson:"openProcesses"`
	Subprocesses  map[string]*Subprocess `bson:"subprocesses"`
}

func (p Process) IsFinished() bool {
	return p.OpenProcesses == 0 && p.Ok+p.Nok == p.Total
}

func (p Process) IsActive() bool {
	return p.LastUpdate.After(time.Now().Add(-24 * time.Hour))
}

func (p *Process) Increment(message ProcessBody) {
	p.LastUpdate = time.Now()
	p.Total += message.Following
	if message.Success {
		p.Ok++
	} else {
		p.Nok++
	}
}

func (p *Process) CloseSubprocess(subprocess *Subprocess) (closed []string) {
	parentId := subprocess.ParentProcess

	p.OpenProcesses--
	parent, ok := p.Subprocesses[parentId]
	if !ok {
		return
	}

	if subprocess.IsOk() {
		parent.Ok++
	} else {
		parent.Nok++
	}
	if parent.IsFinished() {
		closed = append([]string{parentId}, p.CloseSubprocess(parent)...)
	}

	return
}

func (p Subprocess) IsFinished() bool {
	return p.Ok+p.Nok == p.Total
}

func (p Subprocess) IsOk() bool {
	return p.Nok <= 0
}

func (p *Subprocess) Increment(message ProcessBody) {
	p.Total += message.Following
	if message.Success {
		p.Ok++
	} else {
		p.Nok++
	}
}

func (p Process) IsOk() bool {
	return p.Nok <= 0
}

func (p ProcessWithId) IntoProcess() Process {
	return Process{
		Id:            p.Id,
		CorrelationId: p.CorrelationId,
		TopologyId:    p.TopologyId,
		ProcessId:     p.ProcessId,
		Ok:            p.Ok,
		Nok:           p.Nok,
		Created:       p.Created,
		Finished:      p.Finished,
		Total:         p.Total,
		OpenProcesses: p.OpenProcesses,
		Subprocesses:  p.Subprocesses,
		LastUpdate:    time.Now(),
	}
}
