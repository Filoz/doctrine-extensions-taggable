<?php

use Anh\Taggable\Tool\BaseTestCase;

class TaggableManagerTest extends BaseTestCase
{
    public function testGetTagClass()
    {
        $this->assertEquals(self::TAG, $this->manager->getTagClass());
    }

    public function testGetTaggingClass()
    {
        $this->assertEquals(self::TAGGING, $this->manager->getTaggingClass());
    }

    public function testCreateTag()
    {
        $this->assertInstanceOf(self::TAG, $this->manager->createTag());
    }

    public function testCreateTagging()
    {
        $this->assertInstanceOf(self::TAGGING, $this->manager->createTagging());
    }

    public function testLoadOrCreateTag()
    {
        $tag1 = $this->manager->loadOrCreateTag('test');
        $this->assertInstanceOf(self::TAG, $tag1);
        $tag2 = $this->manager->loadOrCreateTag('test');
        $this->assertEquals($tag1->getId(), $tag2->getId());
    }

    public function testLoadOrCreateTags()
    {
        $tag = $this->manager->loadOrCreateTag('test');
        $tags = $this->manager->loadOrCreateTags(array('test', 'another'));
        $this->assertTrue(is_array($tags));
        $this->assertEquals($tag, $tags[0]);
        $this->assertEquals($tags[1]->getName(), 'another');
    }

    public function testLoadTags()
    {
        $article = $this->loadArticle();

        $tags = $this->manager->loadTags($article);
        $this->assertInstanceOf('\Doctrine\Common\Collections\ArrayCollection', $tags);
        $this->assertEquals(3, $tags->count());
        $this->assertEquals('pulsar', $tags[0]->getName());
        $this->assertEquals('nebula', $tags[1]->getName());
        $this->assertEquals('galaxy', $tags[2]->getName());
    }

    public function testDeleteTagging()
    {
        // $article = $this->createArticle();
        $article = $this->loadArticle();
        $tags = $this->manager->loadOrCreateTags(array('andromeda', 'orion'));
        $article->addTags($tags);
        $this->manager->deleteTagging($article);
        $this->em->flush();

        $repository = $this->em->getRepository(self::TAGGING);
        $tagging = $repository->findBy(array(
            'resourceId' => $article->getId(),
            'resourceType' => $article->getTaggableType()
        ));
        $this->assertTrue(empty($tagging));
    }

    public function testSyncTagging()
    {
        $article = $this->createArticle();
        $tags = $this->manager->loadOrCreateTags(array('tag1', 'tag2', 'tag3'));
        $article->addTags($tags);
        $this->em->persist($article);
        $this->em->flush();

        $id = $article->getId();
        $this->em->detach($article);

        $article = $this->em->find(static::FIXTURE, $id);
        $this->assertEquals(6, $article->getTags()->count());
    }

    public function testTagUniqueConstraint()
    {
        $this->setExpectedException('\Doctrine\DBAL\DBALException');

        $tag1 = $this->manager->createTag();
        $tag1->setName('test');
        $this->em->persist($tag1);

        $tag2 = $this->manager->createTag();
        $tag2->setName('test');
        $this->em->persist($tag2);

        $this->em->flush();
    }

    public function testTaggingUniqueConstraint()
    {
        $this->setExpectedException('\Doctrine\DBAL\DBALException');

        $article = $this->createArticle();
        $tag = $this->manager->loadOrCreateTag('test');

        $tagging1 = $this->manager->createTagging();
        $tagging1->setResource($article);
        $tagging1->setTag($tag);
        $this->em->persist($tagging1);

        $tagging2 = $this->manager->createTagging();
        $tagging2->setResource($article);
        $tagging2->setTag($tag);
        $this->em->persist($tagging2);

        $this->em->flush();
    }
}