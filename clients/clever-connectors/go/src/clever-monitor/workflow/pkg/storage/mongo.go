package storage

import (
	"time"
	"fmt"

	"gopkg.in/mgo.v2"
	"gopkg.in/mgo.v2/bson"

	"clever-monitor/utils/logger"
)

type Mongo struct {
	host               string
	db                 string
	editorCollection   string
	workflowCollection string
	logger             logger.Logger
	session            *mgo.Session
}

type EditorRecord struct {
	Id   string `bson:"_id,omitempty"`
	Json string `bson:"json"`
}

type WorkflowRecord struct {
	Id       string `bson:"_id,omitempty"`
	EditorId string `bson:"editor_id,omitempty"`
	Json     string `bson:"json"`
}

// Returns the pointer to newly created mongo storage instance
func NewMongo(host string, db string, edColl string, wfColl string, logger logger.Logger) (*Mongo) {
	return &Mongo{
		host:               host,
		db:                 db,
		editorCollection:   edColl,
		workflowCollection: wfColl,
		logger:             logger,
	}
}

// Create persists new record to mongo storage and returns it's id
func (s *Mongo) Create(editorConfig string, workflowConfigs []string) (string, error) {
	c := s.getActiveSession().DB(s.db).C(s.editorCollection)
	editorId := bson.NewObjectId().Hex()
	eRec := EditorRecord{
		Id:   editorId,
		Json: editorConfig,
	}

	err := c.Insert(eRec)
	if err != nil {
		return "", err
	}

	c = s.getActiveSession().DB(s.db).C(s.workflowCollection)
	for _, wf := range workflowConfigs {
		wfRec := WorkflowRecord{
			Id:       bson.NewObjectId().Hex(),
			EditorId: editorId,
			Json:     wf,
		}

		err := c.Insert(wfRec)
		if err != nil {
			return editorId, err
		}
	}

	return editorId, nil
}

// Delete removes the editor document by it's unique id and all related workflow documents
func (s *Mongo) Delete(id string) (error) {
	c := s.getActiveSession().DB(s.db).C(s.editorCollection)
	err := c.RemoveId(id)
	if err != nil {
		return err
	}

	c = s.getActiveSession().DB(s.db).C(s.workflowCollection)
	_, err = c.RemoveAll(bson.M{"editor_id": id})
	if err != nil {
		return err
	}

	return nil
}

// FindEditorConfig tries to find up the record in editor collection by it's id
func (s *Mongo) FindEditorConfig(id string) (*EditorRecord, error) {
	var record EditorRecord

	c := s.getActiveSession().DB(s.db).C(s.editorCollection)
	err := c.FindId(id).One(&record)

	return &record, err
}

// FindWorkflowConfig tries to find up the record in workflow collection by it's id
func (s *Mongo) FindWorkflowConfig(id string) (*WorkflowRecord, error) {
	var record WorkflowRecord

	c := s.getActiveSession().DB(s.db).C(s.workflowCollection)
	err := c.FindId(id).One(&record)

	return &record, err
}

// FindWorkflowConfig tries to find up the record in workflow collection by it's id
func (s *Mongo) FindAllWorkflowConfigs(editorId string) ([]*WorkflowRecord, error) {
	var records []*WorkflowRecord

	c := s.getActiveSession().DB(s.db).C(s.workflowCollection)
	err := c.Find(bson.M{"editor_id": editorId}).All(&records)

	if err != nil {
		return []*WorkflowRecord{}, err
	}

	return records, nil
}

// DropCollection drops current editorCollection
func (s *Mongo) ClearStorage() {
	s.getActiveSession().DB(s.db).C(s.editorCollection).DropCollection()
	s.getActiveSession().DB(s.db).C(s.workflowCollection).DropCollection()
}

// Connect creates new connection to mongodb instance
func (s *Mongo) Connect() {
	var err error
	s.logger.Info(fmt.Sprintf("Mongo DB connecting to: %s", s.host), nil)
	s.session, err = mgo.Dial(s.host)

	if err != nil {
		s.logger.Error(fmt.Sprintf("Mongo DB error: %s", err), logger.Context{"error": err})
		s.reconnect()
		return
	}

	s.logger.Info(fmt.Sprintf("Mongo DB is connected to: %s", s.host), nil)
}

// Disconnect cancels existing mongodb connection
func (s *Mongo) Disconnect() {
	if s.session != nil {
		s.session.Close()
	}
}

// reconnect tries to create new mongodb connection
func (s *Mongo) reconnect() {
	s.logger.Info("Waiting 1s.", nil)
	time.Sleep(time.Second * 1)
	s.Disconnect()
	s.Connect()
}

// getActiveSession always returns the active mongo session
func (s *Mongo) getActiveSession() (*mgo.Session) {
	return s.session.Clone()
}
