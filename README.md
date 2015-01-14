# MongoObject - PHP objects stored in Mongo #

[![Build Status](https://travis-ci.org/dintel/mongo-object.svg)](https://travis-ci.org/dintel/mongo-object)

MongoObject simplifies storing objects in Mongo database. Currently it provides
following features:
- Object class that can be used as base class for all your classes that has to
  be saved into Mongo
- Mapper class that simplifies fetching of objects from Mongo
- MapperService class that can be registered as Zend Framework 2 service factory
  for Mapper class.
